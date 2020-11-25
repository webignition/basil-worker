<?php

declare(strict_types=1);

namespace App\MessageDispatcher;

use App\Event\SourcesAddedEvent;
use App\Message\TimeoutCheck;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

class TimeoutCheckMessageDispatcher implements EventSubscriberInterface
{
    private const MILLISECONDS_PER_SECOND = 1000;

    private MessageBusInterface $messageBus;
    private int $recheckPeriodInSeconds;

    public function __construct(MessageBusInterface $messageBus, int $recheckPeriodInSeconds)
    {
        $this->messageBus = $messageBus;
        $this->recheckPeriodInSeconds = $recheckPeriodInSeconds;
    }

    public static function getSubscribedEvents()
    {
        return [
            SourcesAddedEvent::class => [
                ['dispatch', 0],
            ],
        ];
    }

    public function dispatch(): void
    {
        $message = new TimeoutCheck();
        $envelope = new Envelope($message, [
            new DelayStamp($this->recheckPeriodInSeconds * self::MILLISECONDS_PER_SECOND)
        ]);
        $this->messageBus->dispatch($envelope);
    }
}
