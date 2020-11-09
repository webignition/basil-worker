<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\SourceCompile\SourceCompileFailureEvent;
use App\Message\SendCallback;
use App\Model\Callback\CompileFailure;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class SourceCompileFailureEventSubscriber implements EventSubscriberInterface
{
    private MessageBusInterface $messageBus;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public static function getSubscribedEvents()
    {
        return [
            SourceCompileFailureEvent::class => [
                ['dispatchSendCallbackMessage', 0],
            ],
        ];
    }

    public function dispatchSendCallbackMessage(SourceCompileFailureEvent $event): void
    {
        $callback = new CompileFailure($event->getOutput());
        $message = new SendCallback($callback);

        $this->messageBus->dispatch($message);
    }
}
