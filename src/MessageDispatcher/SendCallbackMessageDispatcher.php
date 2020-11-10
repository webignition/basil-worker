<?php

declare(strict_types=1);

namespace App\MessageDispatcher;

use App\Event\Callback\CallbackHttpExceptionEvent;
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
                ['dispatchForHttpExceptionEvent', 0],
            ],
        ];
    }

    public function dispatchForHttpExceptionEvent(CallbackHttpExceptionEvent $event): void
    {
        $message = new SendCallback($event->getCallback());

        $this->messageBus->dispatch($message);
    }
}
