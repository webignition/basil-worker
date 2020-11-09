<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\JobCompletedEvent;
use App\Services\JobStateMutator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class JobCompletedEventSubscriber implements EventSubscriberInterface
{
    private JobStateMutator $jobStateMutator;

    public function __construct(JobStateMutator $jobStateMutator)
    {
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
        $this->jobStateMutator->setExecutionComplete();
    }
}
