<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageDispatcher;

use App\Event\Callback\CallbackHttpExceptionEvent;
use App\Message\SendCallback;
use App\MessageDispatcher\SendCallbackMessageDispatcher;
use App\Model\Callback\CallbackInterface;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Model\TestCallback;
use GuzzleHttp\Exception\ConnectException;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Transport\InMemoryTransport;

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

    public function testDispatchForHttpExceptionEvent()
    {
        $callback = new TestCallback();
        $exception = \Mockery::mock(ConnectException::class);
        $event = new CallbackHttpExceptionEvent($callback, $exception);

        $this->messageDispatcher->dispatchForHttpExceptionEvent($event);

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

    private function assertMessageTransportQueue(CallbackInterface $expectedCallback): void
    {
        $queue = $this->messengerTransport->get();
        self::assertCount(1, $queue);
        self::assertIsArray($queue);

        $expectedQueuedMessage = new SendCallback($expectedCallback);

        self::assertEquals($expectedQueuedMessage, $queue[0]->getMessage());
    }
}
