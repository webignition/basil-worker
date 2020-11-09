<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Job;
use App\Event\JobCancelledEvent;
use App\Event\JobCompletedEvent;
use App\Event\SourceCompile\SourceCompileFailureEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class JobStateMutator implements EventSubscriberInterface
{
    private JobStore $jobStore;
    private CompilationWorkflowHandler $compilationWorkflowHandler;
    private ExecutionWorkflowHandler $executionWorkflowHandler;

    public function __construct(
        JobStore $jobStore,
        CompilationWorkflowHandler $compilationWorkflowHandler,
        ExecutionWorkflowHandler $executionWorkflowHandler
    ) {
        $this->jobStore = $jobStore;
        $this->compilationWorkflowHandler = $compilationWorkflowHandler;
        $this->executionWorkflowHandler = $executionWorkflowHandler;
    }

    public static function getSubscribedEvents()
    {
        return [
            JobCancelledEvent::class => [
                ['setExecutionCancelled', 0],
            ],
            JobCompletedEvent::class => [
                ['setExecutionComplete', 0],
            ],
            SourceCompileFailureEvent::class => [
                ['setCompilationFailed', 0],
            ],
        ];
    }

    public function setCompilationRunning(): void
    {
        $this->setIfCurrentState(Job::STATE_COMPILATION_AWAITING, Job::STATE_COMPILATION_RUNNING);
    }

    public function setCompilationFailed(): void
    {
        $this->setIfCurrentState(Job::STATE_COMPILATION_RUNNING, Job::STATE_COMPILATION_FAILED);
    }

    public function setExecutionAwaiting(): void
    {
        $this->conditionallySetState(
            function (): bool {
                return $this->compilationWorkflowHandler->isComplete()
                    && $this->executionWorkflowHandler->isReadyToExecute();
            },
            Job::STATE_EXECUTION_AWAITING
        );
    }

    public function setExecutionRunning(): void
    {
        $this->set(Job::STATE_EXECUTION_RUNNING);
    }

    public function setExecutionComplete(): void
    {
        $this->conditionallySetState(
            function (): bool {
                return $this->executionWorkflowHandler->isComplete();
            },
            Job::STATE_EXECUTION_COMPLETE
        );
    }

    public function setExecutionCancelled(): void
    {
        $this->conditionallySetState(
            function (Job $job): bool {
                return false === $job->isFinished();
            },
            Job::STATE_EXECUTION_CANCELLED
        );
    }

    /**
     * @param Job::STATE_* $state
     */
    private function set(string $state): void
    {
        if ($this->jobStore->hasJob()) {
            $job = $this->jobStore->getJob();
            $job->setState($state);
            $this->jobStore->store($job);
        }
    }

    /**
     * @param Job::STATE_* $currentState
     * @param Job::STATE_* $state
     */
    private function setIfCurrentState(string $currentState, string $state): void
    {
        $this->conditionallySetState(
            function (Job $job) use ($currentState): bool {
                return $currentState === $job->getState();
            },
            $state
        );
    }

    /**
     * @param callable $conditional
     * @param Job::STATE_* $state
     */
    private function conditionallySetState(callable $conditional, string $state): void
    {
        if ($this->jobStore->hasJob()) {
            $job = $this->jobStore->getJob();

            if (true === $conditional($job)) {
                $job->setState($state);
                $this->jobStore->store($job);
            }
        }
    }
}
