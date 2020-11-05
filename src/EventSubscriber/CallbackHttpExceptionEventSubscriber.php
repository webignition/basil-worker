<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\Callback\CallbackHttpExceptionEvent;
use App\Message\SendCallback;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class CallbackHttpExceptionEventSubscriber implements EventSubscriberInterface
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
                ['dispatchSendCallbackMessage', 0],
            ],
        ];
    }

    public function dispatchSendCallbackMessage(CallbackHttpExceptionEvent $event): void
    {
        $message = new SendCallback($event->getCallback());

        $this->messageBus->dispatch($message);
    }
}
