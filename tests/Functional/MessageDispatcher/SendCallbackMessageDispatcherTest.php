<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageDispatcher;

use App\Entity\Test;
use App\Event\Callback\CallbackHttpExceptionEvent;
use App\Event\Callback\CallbackHttpResponseEvent;
use App\Event\CallbackEventInterface;
use App\Event\SourceCompile\SourceCompileFailureEvent;
use App\Event\TestExecuteDocumentReceivedEvent;
use App\Message\SendCallback;
use App\MessageDispatcher\SendCallbackMessageDispatcher;
use App\Model\Callback\CallbackInterface;
use App\Model\Callback\CompileFailure;
use App\Model\Callback\ExecuteDocumentReceived;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Model\TestCallback;
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

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    public function testDispatchForCallbackEvent()
    {
        $callback = \Mockery::mock(CallbackInterface::class);
        $event = \Mockery::mock(CallbackEventInterface::class);
        $event
            ->shouldReceive('getCallback')
            ->andReturn($callback);

        $this->messageDispatcher->dispatchForCallbackEvent($event);

        $this->assertMessageTransportQueue($callback);
    }

    /**
     * @dataProvider subscribesToEventDataProvider
     */
    public function testSubscribesToEvent(
        CallbackEventInterface $event,
        CallbackInterface $expectedQueuedMessageCallback
    ) {
        self::assertCount(0, $this->messengerTransport->get());
        $this->eventDispatcher->dispatch($event);

        $this->assertMessageTransportQueue($expectedQueuedMessageCallback);
    }

    public function subscribesToEventDataProvider(): array
    {
        $httpExceptionEventCallback = new TestCallback();
        $httpResponseExceptionCallback = new TestCallback();

        $sourceCompileFailureEventOutput = \Mockery::mock(ErrorOutputInterface::class);
        $sourceCompileFailureEventCallback = new CompileFailure($sourceCompileFailureEventOutput);

        $document = new Document('data');

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
                'event' => new SourceCompileFailureEvent('/app/source/Test/test.yml', $sourceCompileFailureEventOutput),
                'expectedQueuedMessageCallback' => $sourceCompileFailureEventCallback,
            ],
            ExecuteDocumentReceived::class => [
                'event' => new TestExecuteDocumentReceivedEvent(\Mockery::mock(Test::class), $document),
                'expectedQueuedMessageCallback' => new ExecuteDocumentReceived($document),
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
