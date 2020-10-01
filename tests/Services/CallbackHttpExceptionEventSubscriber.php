<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Event\CallbackHttpExceptionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CallbackHttpExceptionEventSubscriber implements EventSubscriberInterface
{
    private ?CallbackHttpExceptionEvent $event = null;

    public static function getSubscribedEvents()
    {
        return [
            CallbackHttpExceptionEvent::NAME => 'onCallbackHttpException',
        ];
    }

    public function onCallbackHttpException(CallbackHttpExceptionEvent $event): void
    {
        $this->event = $event;
    }

    public function getEvent(): ?CallbackHttpExceptionEvent
    {
        return $this->event;
    }
}
