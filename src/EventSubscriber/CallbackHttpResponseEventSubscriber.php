<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\CallbackHttpResponseEvent;
use App\Message\SendCallback;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class CallbackHttpResponseEventSubscriber implements EventSubscriberInterface
{
    private MessageBusInterface $messageBus;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public static function getSubscribedEvents()
    {
        return [
            CallbackHttpResponseEvent::class => [
                ['dispatchSendCallbackMessage', 0],
            ],
        ];
    }

    public function dispatchSendCallbackMessage(CallbackHttpResponseEvent $event): void
    {
        $message = new SendCallback($event->getCallback());

        $this->messageBus->dispatch($message);
    }
}
