<?php

declare(strict_types=1);

namespace App\MessageDispatcher;

use App\Event\Callback\CallbackHttpExceptionEvent;
use App\Event\Callback\CallbackHttpResponseEvent;
use App\Event\FooCallbackEventInterface;
use App\Event\FooTestExecuteDocumentReceivedEvent;
use App\Event\SourceCompile\FooSourceCompileFailureEvent;
use App\Message\SendCallback;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class SendCallbackMessageDispatcher implements EventSubscriberInterface
{
    private MessageBusInterface $messageBus;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public static function getSubscribedEvents()
    {
        return [
            CallbackHttpExceptionEvent::class => [
                ['dispatchForCallbackEvent', 0],
            ],
            CallbackHttpResponseEvent::class => [
                ['dispatchForCallbackEvent', 0],
            ],
            FooSourceCompileFailureEvent::class => [
                ['dispatchForCallbackEvent', 0],
            ],
            FooTestExecuteDocumentReceivedEvent::class => [
                ['dispatchForCallbackEvent', 0],
            ],
        ];
    }

    public function dispatchForCallbackEvent(FooCallbackEventInterface $event): void
    {
        $this->messageBus->dispatch(
            new SendCallback($event->getCallback())
        );
    }
}
