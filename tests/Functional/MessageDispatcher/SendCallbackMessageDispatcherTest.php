<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageDispatcher;

use App\Entity\Callback\CallbackInterface;
use App\Entity\Callback\CompileFailureCallback;
use App\Entity\Callback\DelayedCallback;
use App\Entity\Callback\ExecuteDocumentReceivedCallback;
use App\Event\Callback\CallbackHttpExceptionEvent;
use App\Event\Callback\CallbackHttpResponseEvent;
use App\Event\CallbackEventInterface;
use App\Event\SourceCompile\SourceCompileFailureEvent;
use App\Event\TestExecuteDocumentReceivedEvent;
use App\Message\SendCallback;
use App\MessageDispatcher\SendCallbackMessageDispatcher;
use App\Repository\CallbackRepository;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\Entity\MockTest;
use App\Tests\Model\Entity\Callback\TestCallbackEntity;
use App\Tests\Model\TestCallback;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Response;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\StampInterface;
use Symfony\Component\Messenger\Transport\InMemoryTransport;
use webignition\BasilCompilerModels\ErrorOutputInterface;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;
use webignition\YamlDocument\Document;

class SendCallbackMessageDispatcherTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;
    use TestClassServicePropertyInjectorTrait;

    private SendCallbackMessageDispatcher $messageDispatcher;
    private InMemoryTransport $messengerTransport;
    private EventDispatcherInterface $eventDispatcher;
    private CallbackRepository $callbackRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    /**
     * @dataProvider dispatchForCallbackEventDataProvider
     *
     * @param CallbackInterface $callback
     * @param string|null $expectedEnvelopeNotContainsStampsOfType
     * @param array<string, array<int, StampInterface>> $expectedEnvelopeContainsStampCollections
     */
    public function testDispatchForCallbackEvent(
        CallbackInterface $callback,
        ?string $expectedEnvelopeNotContainsStampsOfType,
        array $expectedEnvelopeContainsStampCollections
    ) {
        $event = \Mockery::mock(CallbackEventInterface::class);
        $event
            ->shouldReceive('getCallback')
            ->andReturn($callback);

        $this->messageDispatcher->dispatchForCallbackEvent($event);

        $callback = $this->callbackRepository->findOneBy([]);
        self::assertInstanceOf(CallbackInterface::class, $callback);

        $this->assertMessageTransportQueue(
            new SendCallback($callback),
            $expectedEnvelopeNotContainsStampsOfType,
            $expectedEnvelopeContainsStampCollections
        );
    }

    public function dispatchForCallbackEventDataProvider(): array
    {
        $nonDelayedCallback = new TestCallback();
        $delayedCallbackRetryCount1 = DelayedCallback::create(
            (new TestCallback())
                ->withRetryCount(1)
        );

        return [
            'non-delayed' => [
                'callback' => $nonDelayedCallback,
                'expectedEnvelopeNotContainsStampsOfType' => DelayStamp::class,
                'expectedEnvelopeContainsStampCollections' => [],
            ],
            'delayed, retry count 1' => [
                'callback' => $delayedCallbackRetryCount1,
                'expectedEnvelopeNotContainsStampsOfType' => null,
                'expectedEnvelopeContainsStampCollections' => [
                    DelayStamp::class => [
                        new DelayStamp(1000),
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider subscribesToEventDataProvider
     */
    public function testSubscribesToEvent(
        CallbackEventInterface $event,
        CallbackInterface $expectedQueuedMessageCallback
    ) {
        $callback = $event->getCallback();
        self::assertSame(CallbackInterface::STATE_AWAITING, $callback->getState());
        self::assertCount(0, $this->messengerTransport->get());

        $this->eventDispatcher->dispatch($event);
        self::assertSame(CallbackInterface::STATE_QUEUED, $callback->getState());
        $this->assertMessageTransportQueue(new SendCallback($expectedQueuedMessageCallback), null, []);
    }

    public function subscribesToEventDataProvider(): array
    {
        $httpExceptionEventCallback = TestCallbackEntity::createWithUniquePayload();
        $httpResponseExceptionCallback = TestCallbackEntity::createWithUniquePayload();

        $sourceCompileFailureEventOutput = \Mockery::mock(ErrorOutputInterface::class);
        $sourceCompileFailureEventOutput
            ->shouldReceive('getData')
            ->andReturn([
                'unique' => md5(random_bytes(16)),
            ]);

        $sourceCompileFailureEventCallback = new CompileFailureCallback($sourceCompileFailureEventOutput);

        $document = new Document('data');
        $testExecuteDocumentReceivedEventCallback = new ExecuteDocumentReceivedCallback($document);

        return [
            CallbackHttpExceptionEvent::class => [
                'event' => new CallbackHttpExceptionEvent(
                    $httpExceptionEventCallback,
                    \Mockery::mock(ConnectException::class)
                ),
                'expectedQueuedMessage' => $httpExceptionEventCallback,
            ],
            CallbackHttpResponseEvent::class => [
                'event' => new CallbackHttpResponseEvent($httpResponseExceptionCallback, new Response(503)),
                'expectedQueuedMessage' => $httpResponseExceptionCallback,
            ],
            SourceCompileFailureEvent::class => [
                'event' => new SourceCompileFailureEvent(
                    '/app/source/Test/test.yml',
                    $sourceCompileFailureEventOutput,
                    $sourceCompileFailureEventCallback
                ),
                'expectedQueuedMessage' => $sourceCompileFailureEventCallback,
            ],
            TestExecuteDocumentReceivedEvent::class => [
                'event' => new TestExecuteDocumentReceivedEvent(
                    (new MockTest())->getMock(),
                    $document,
                    $testExecuteDocumentReceivedEventCallback
                ),
                'expectedQueuedMessage' => $testExecuteDocumentReceivedEventCallback,
            ],
        ];
    }

    /**
     * @param SendCallback $expectedMessage
     * @param string|null $expectedEnvelopeNotContainsStampsOfType
     * @param array<string, array<int, StampInterface>> $expectedEnvelopeContainsStampCollections
     */
    private function assertMessageTransportQueue(
        SendCallback $expectedMessage,
        ?string $expectedEnvelopeNotContainsStampsOfType,
        array $expectedEnvelopeContainsStampCollections
    ): void {
        $queue = $this->messengerTransport->get();
        self::assertCount(1, $queue);
        self::assertIsArray($queue);

        $envelope = $queue[0];
        self::assertInstanceOf(Envelope::class, $envelope);

        self::assertEquals($expectedMessage, $envelope->getMessage());

        if (is_string($expectedEnvelopeNotContainsStampsOfType)) {
            $this->assertEnvelopeNotContainsStampsOfType($envelope, $expectedEnvelopeNotContainsStampsOfType);
        }

        foreach ($expectedEnvelopeContainsStampCollections as $expectedStampCollection) {
            foreach ($expectedStampCollection as $expectedStampIndex => $expectedStamp) {
                $this->assertEnvelopeContainsStamp($envelope, $expectedStamp, $expectedStampIndex);
            }
        }
    }

    private function assertEnvelopeNotContainsStampsOfType(Envelope $envelope, string $type): void
    {
        $stamps = $envelope->all();
        self::assertArrayNotHasKey($type, $stamps);
    }

    private function assertEnvelopeContainsStamp(
        Envelope $envelope,
        StampInterface $expectedStamp,
        int $expectedStampIndex
    ): void {
        $stamps = $envelope->all();
        $typeIndex = get_class($expectedStamp);

        self::assertArrayHasKey($typeIndex, $stamps);

        $typeStamps = $stamps[$typeIndex] ?? [];
        $actualStamp = $typeStamps[$expectedStampIndex] ?? null;

        self::assertEquals($expectedStamp, $actualStamp);
    }
}
