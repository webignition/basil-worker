<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Event\JobTimeoutEvent;
use App\Message\TimeoutCheck;
use App\MessageDispatcher\TimeoutCheckMessageDispatcher;
use App\Services\JobStore;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class TimeoutCheckHandler implements MessageHandlerInterface
{
    private JobStore $jobStore;
    private TimeoutCheckMessageDispatcher $timeoutCheckMessageDispatcher;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        JobStore $jobStore,
        TimeoutCheckMessageDispatcher $timeoutCheckMessageDispatcher,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->jobStore = $jobStore;
        $this->timeoutCheckMessageDispatcher = $timeoutCheckMessageDispatcher;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function __invoke(TimeoutCheck $timeoutCheck): void
    {
        if (false === $this->jobStore->hasJob()) {
            return;
        }

        $job = $this->jobStore->getJob();
        if ($job->hasReachedMaximumDuration()) {
            $this->eventDispatcher->dispatch(new JobTimeoutEvent($job->getMaximumDurationInSeconds()));
        } else {
            $this->timeoutCheckMessageDispatcher->dispatch();
        }
    }
}
