<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Job;
use App\Entity\Test;
use App\Event\SourceCompile\SourceCompileFailureEvent;
use App\Event\SourceCompile\SourceCompileSuccessEvent;
use App\Event\SourcesAddedEvent;
use App\Event\TestExecuteCompleteEvent;
use App\Event\TestFailedEvent;
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
            SourcesAddedEvent::class => [
                ['setCompilationRunning', 100],
            ],
            SourceCompileFailureEvent::class => [
                ['setCompilationFailed', 100],
            ],
            SourceCompileSuccessEvent::class => [
                ['setExecutionAwaiting', 0],
            ],
            TestExecuteCompleteEvent::class => [
                ['setExecutionComplete', 100],
            ],
            TestFailedEvent::class => [
                ['setExecutionCancelledFromTestFailedEvent', 10],
            ],
        ];
    }

    public function setExecutionCancelledFromTestFailedEvent(TestFailedEvent $event): void
    {
        $test = $event->getTest();

        if (Test::STATE_FAILED === $test->getState()) {
            $this->setExecutionCancelled();
        }
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
        if ($this->jobStore->hasJob()) {
            $this->set($this->jobStore->getJob(), Job::STATE_EXECUTION_RUNNING);
        }
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
    private function set(Job $job, string $state): void
    {
        $job->setState($state);
        $this->jobStore->store($job);
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
                $this->set($job, $state);
            }
        }
    }
}
