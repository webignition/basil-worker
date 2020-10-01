<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Event\SourceCompileFailureEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SourceCompileFailureEventSubscriber implements EventSubscriberInterface
{
    private ?SourceCompileFailureEvent $event = null;

    public static function getSubscribedEvents()
    {
        return [
            SourceCompileFailureEvent::NAME => 'onSourceCompileFailureEvent',
        ];
    }

    public function onSourceCompileFailureEvent(SourceCompileFailureEvent $event): void
    {
        $this->event = $event;
    }

    public function getEvent(): ?SourceCompileFailureEvent
    {
        return $this->event;
    }
}
