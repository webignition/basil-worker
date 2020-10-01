<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Event\SourcesAddedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SourcesAddedEventSubscriber implements EventSubscriberInterface
{
    private ?SourcesAddedEvent $event = null;

    public static function getSubscribedEvents()
    {
        return [
            SourcesAddedEvent::NAME => 'onSourcesAdded',
        ];
    }

    public function onSourcesAdded(SourcesAddedEvent $event): void
    {
        $this->event = $event;
    }

    public function getEvent(): ?SourcesAddedEvent
    {
        return $this->event;
    }
}
