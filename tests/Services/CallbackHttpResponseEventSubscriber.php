<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Event\CallbackHttpResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CallbackHttpResponseEventSubscriber implements EventSubscriberInterface
{
    private ?CallbackHttpResponseEvent $event = null;

    public static function getSubscribedEvents()
    {
        return [
            CallbackHttpResponseEvent::class => 'onCallbackHttpResponse',
        ];
    }

    public function onCallbackHttpResponse(CallbackHttpResponseEvent $event): void
    {
        $this->event = $event;
    }

    public function getEvent(): ?CallbackHttpResponseEvent
    {
        return $this->event;
    }
}
