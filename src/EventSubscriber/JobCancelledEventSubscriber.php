<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\JobCancelledEvent;
use App\Services\JobStateMutator;
use App\Services\JobStore;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class JobCancelledEventSubscriber implements EventSubscriberInterface
{
    private JobStore $jobStore;
    private JobStateMutator $jobStateMutator;

    public function __construct(JobStore $jobStore, JobStateMutator $jobStateMutator)
    {
        $this->jobStore = $jobStore;
        $this->jobStateMutator = $jobStateMutator;
    }

    public static function getSubscribedEvents()
    {
        return [
            JobCancelledEvent::class => [
                ['setJobStateToCancelled', 0],
            ],
        ];
    }

    public function setJobStateToCancelled(): void
    {
        $job = $this->jobStore->getJob();

        if (false === $job->isFinished()) {
            $this->jobStateMutator->setExecutionCancelled();
        }
    }
}
