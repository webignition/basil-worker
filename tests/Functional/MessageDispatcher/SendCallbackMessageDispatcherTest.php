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
use webignition\YamlDocument\Document;

class SendCallbackMessageDispatcherTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;

    private SendCallbackMessageDispatcher $messageDispatcher;
    private InMemoryTransport $messengerTransport;
    private EventDispatcherInterface $eventDispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $messageDispatcher = self::$container->get(SendCallbackMessageDispatcher::class);
        self::assertInstanceOf(SendCallbackMessageDispatcher::class, $messageDispatcher);
        if ($messageDispatcher instanceof SendCallbackMessageDispatcher) {
            $this->messageDispatcher = $messageDispatcher;
        }

        $messengerTransport = self::$container->get('messenger.transport.async');
        self::assertInstanceOf(InMemoryTransport::class, $messengerTransport);
        if ($messengerTransport instanceof InMemoryTransport) {
            $this->messengerTransport = $messengerTransport;
        }

        $eventDispatcher = self::$container->get(EventDispatcherInterface::class);
        self::assertInstanceOf(EventDispatcherInterface::class, $eventDispatcher);
        if ($eventDispatcher instanceof EventDispatcherInterface) {
            $this->eventDispatcher = $eventDispatcher;
        }
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

    public function testSubscribesToCallbackHttpExceptionEvent()
    {
        self::assertCount(0, $this->messengerTransport->get());

        $callback = new TestCallback();
        $exception = \Mockery::mock(ConnectException::class);
        $event = new CallbackHttpExceptionEvent($callback, $exception);

        $this->eventDispatcher->dispatch($event);

        $this->assertMessageTransportQueue($callback);
    }

    public function testSubscribesToCallbackHttpResponseEvent()
    {
        self::assertCount(0, $this->messengerTransport->get());

        $callback = new TestCallback();
        $response = new Response(503);
        $event = new CallbackHttpResponseEvent($callback, $response);

        $this->eventDispatcher->dispatch($event);

        $this->assertMessageTransportQueue($callback);
    }

    public function testSubscribesToSourceCompileFailureEvent()
    {
        self::assertCount(0, $this->messengerTransport->get());

        $errorOutput = \Mockery::mock(ErrorOutputInterface::class);
        $callback = new CompileFailure($errorOutput);
        $event = new SourceCompileFailureEvent('/app/source/Test/test.yml', $errorOutput);

        $this->eventDispatcher->dispatch($event);

        $this->assertMessageTransportQueue($callback);
    }

    public function testSubscribesToTestExecuteDocumentReceivedEvent()
    {
        self::assertCount(0, $this->messengerTransport->get());

        $test = \Mockery::mock(Test::class);
        $document = new Document('');

        $event = new TestExecuteDocumentReceivedEvent($test, $document);
        $callback = new ExecuteDocumentReceived($document);

        $this->eventDispatcher->dispatch($event);

        $this->assertMessageTransportQueue($callback);
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
