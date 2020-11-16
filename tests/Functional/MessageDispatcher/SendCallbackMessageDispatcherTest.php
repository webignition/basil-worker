<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageDispatcher;

use App\Entity\Callback\CallbackInterface;
use App\Entity\Callback\CompileFailureCallback;
use App\Entity\Callback\ExecuteDocumentReceivedCallback;
use App\Event\Callback\CallbackHttpExceptionEvent;
use App\Event\Callback\CallbackHttpResponseEvent;
use App\Event\CallbackEventInterface;
use App\Event\SourceCompile\SourceCompileFailureEvent;
use App\Event\TestExecuteDocumentReceivedEvent;
use App\Message\SendCallback;
use App\MessageDispatcher\SendCallbackMessageDispatcher;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\Entity\MockTest;
use App\Tests\Model\Entity\Callback\TestCallbackEntity;
use App\Tests\Services\TestCallbackEventFactory;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Response;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Psr\EventDispatcher\EventDispatcherInterface;
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
    private TestCallbackEventFactory $testCallbackEventFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    public function testDispatchForCallbackEvent()
    {
        $event = $this->testCallbackEventFactory->createEmptyPayloadSourceCompileFailureEvent();
        $callback = $event->getCallback();
        self::assertSame(CallbackInterface::STATE_AWAITING, $callback->getState());

        $this->messageDispatcher->dispatchForCallbackEvent($event);
        self::assertSame(CallbackInterface::STATE_QUEUED, $callback->getState());
        $this->assertMessageTransportQueue($event->getCallback());
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
        $this->assertMessageTransportQueue($expectedQueuedMessageCallback);
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
                'expectedQueuedMessageCallback' => $httpExceptionEventCallback,
            ],
            CallbackHttpResponseEvent::class => [
                'event' => new CallbackHttpResponseEvent($httpResponseExceptionCallback, new Response(503)),
                'expectedQueuedMessageCallback' => $httpResponseExceptionCallback,
            ],
            SourceCompileFailureEvent::class => [
                'event' => new SourceCompileFailureEvent(
                    '/app/source/Test/test.yml',
                    $sourceCompileFailureEventOutput,
                    $sourceCompileFailureEventCallback
                ),
                'expectedQueuedMessageCallback' => $sourceCompileFailureEventCallback,
            ],
            TestExecuteDocumentReceivedEvent::class => [
                'event' => new TestExecuteDocumentReceivedEvent(
                    (new MockTest())->getMock(),
                    $document,
                    $testExecuteDocumentReceivedEventCallback
                ),
                'expectedQueuedMessageCallback' => $testExecuteDocumentReceivedEventCallback,
            ],
        ];
    }

    private function assertMessageTransportQueue(CallbackInterface $expectedCallback): void
    {
        $queue = $this->messengerTransport->get();
        self::assertCount(1, $queue);
        self::assertIsArray($queue);

        $expectedQueuedMessage = new SendCallback($expectedCallback);

        self::assertEquals($expectedQueuedMessage, $queue[0]->getMessage());
    }
}
