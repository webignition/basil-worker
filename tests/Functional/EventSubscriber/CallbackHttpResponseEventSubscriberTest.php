<?php

declare(strict_types=1);

namespace App\Tests\Functional\EventSubscriber;

use App\Event\Callback\CallbackHttpResponseEvent;
use App\EventSubscriber\CallbackHttpResponseEventSubscriber;
use App\Message\SendCallback;
use App\Model\Callback\CallbackInterface;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Model\TestCallback;
use GuzzleHttp\Psr7\Response;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Transport\InMemoryTransport;

class CallbackHttpResponseEventSubscriberTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;

    private CallbackHttpResponseEventSubscriber $eventSubscriber;
    private InMemoryTransport $messengerTransport;

    protected function setUp(): void
    {
        parent::setUp();

        $eventSubscriber = self::$container->get(CallbackHttpResponseEventSubscriber::class);
        if ($eventSubscriber instanceof CallbackHttpResponseEventSubscriber) {
            $this->eventSubscriber = $eventSubscriber;
        }

        $messengerTransport = self::$container->get('messenger.transport.async');
        if ($messengerTransport instanceof InMemoryTransport) {
            $this->messengerTransport = $messengerTransport;
        }
    }

    public function testDispatchSendCallbackMessage()
    {
        $callback = new TestCallback();
        $response = new Response(503);
        $event = new CallbackHttpResponseEvent($callback, $response);

        $this->eventSubscriber->dispatchSendCallbackMessage($event);

        $this->assertMessageTransportQueue($callback);
    }

    public function testIntegration()
    {
        self::assertCount(0, $this->messengerTransport->get());

        $callback = new TestCallback();
        $response = new Response(503);
        $event = new CallbackHttpResponseEvent($callback, $response);

        $eventDispatcher = self::$container->get(EventDispatcherInterface::class);
        if ($eventDispatcher instanceof EventDispatcherInterface) {
            $eventDispatcher->dispatch($event);
        }

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
