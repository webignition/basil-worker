<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\JobCancelledEvent;
use App\Services\JobStateMutator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class JobCancelledEventSubscriber implements EventSubscriberInterface
{
    private JobStateMutator $jobStateMutator;

    public function __construct(JobStateMutator $jobStateMutator)
    {
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
        $this->jobStateMutator->setExecutionCancelled();
    }
}
