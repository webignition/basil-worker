<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Job;
use App\Event\JobCompletedEvent;
use App\Services\JobStateMutator;
use App\Services\JobStore;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class JobCompletedEventSubscriber implements EventSubscriberInterface
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
            JobCompletedEvent::class => [
                ['setJobStateToCompleted', 0],
            ],
        ];
    }

    public function setJobStateToCompleted(): void
    {
        $job = $this->jobStore->getJob();

        if (Job::STATE_EXECUTION_RUNNING === $job->getState()) {
            $this->jobStateMutator->setExecutionComplete();
        }
    }
}
