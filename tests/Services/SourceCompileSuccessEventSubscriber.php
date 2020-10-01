<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Event\SourceCompileSuccessEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SourceCompileSuccessEventSubscriber implements EventSubscriberInterface
{
    private ?SourceCompileSuccessEvent $event = null;

    public static function getSubscribedEvents()
    {
        return [
            SourceCompileSuccessEvent::NAME => 'onSourceCompileSuccessEvent',
        ];
    }

    public function onSourceCompileSuccessEvent(SourceCompileSuccessEvent $event): void
    {
        $this->event = $event;
    }

    public function getEvent(): ?SourceCompileSuccessEvent
    {
        return $this->event;
    }
}
