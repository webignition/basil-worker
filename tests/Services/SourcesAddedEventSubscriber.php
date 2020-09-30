<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Event\SourcesAddedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SourcesAddedEventSubscriber implements EventSubscriberInterface
{
    public const STATE_NO_EVENTS_HANDLED = 'no events handled';
    public const STATE_SOURCES_ADDED_EVENT_HANDLED = 'sources added event handled';

    private string $state;

    public function __construct()
    {
        $this->state = self::STATE_NO_EVENTS_HANDLED;
    }

    public static function getSubscribedEvents()
    {
        return [
            SourcesAddedEvent::NAME => 'onSourcesAdded',
        ];
    }

    public function onSourcesAdded(SourcesAddedEvent $event): void
    {
        $this->state = self::STATE_SOURCES_ADDED_EVENT_HANDLED;
    }

    public function getState(): string
    {
        return $this->state;
    }
}
