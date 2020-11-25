<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Event\JobTimeoutEvent;
use App\Message\TimeoutCheck;
use App\Services\JobStore;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

class TimeoutCheckHandler implements MessageHandlerInterface
{
    private const MILLISECONDS_PER_SECOND = 1000;

    private JobStore $jobStore;
    private MessageBusInterface $messageBus;
    private EventDispatcherInterface $eventDispatcher;
    private int $recheckPeriodInSeconds;

    public function __construct(
        JobStore $jobStore,
        MessageBusInterface $messageBus,
        EventDispatcherInterface $eventDispatcher,
        int $recheckPeriodInSeconds
    ) {
        $this->jobStore = $jobStore;
        $this->messageBus = $messageBus;
        $this->eventDispatcher = $eventDispatcher;
        $this->recheckPeriodInSeconds = $recheckPeriodInSeconds;
    }

    public function __invoke(TimeoutCheck $timeoutCheck): void
    {
        if (false === $this->jobStore->hasJob()) {
            return;
        }

        $job = $this->jobStore->getJob();
        if ($job->hasReachedMaximumDuration()) {
            $this->eventDispatcher->dispatch(new JobTimeoutEvent($job->getMaximumDuration()));
        } else {
            $message = new TimeoutCheck();
            $envelope = new Envelope($message, [
                new DelayStamp($this->recheckPeriodInSeconds * self::MILLISECONDS_PER_SECOND)
            ]);
            $this->messageBus->dispatch($envelope);
        }
    }
}
