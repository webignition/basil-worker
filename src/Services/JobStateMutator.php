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
use App\Model\Workflow\WorkflowInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class JobStateMutator implements EventSubscriberInterface
{
    private JobStore $jobStore;
    private CompilationWorkflowFactory $compilationWorkflowFactory;
    private ExecutionWorkflowFactory $executionWorkflowFactory;

    public function __construct(
        JobStore $jobStore,
        CompilationWorkflowFactory $compilationWorkflowFactory,
        ExecutionWorkflowFactory $executionWorkflowFactory
    ) {
        $this->jobStore = $jobStore;
        $this->compilationWorkflowFactory = $compilationWorkflowFactory;
        $this->executionWorkflowFactory = $executionWorkflowFactory;
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
                ['setExecutionAwaiting', 50],
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
        $this->setIfCurrentState(Job::STATE_COMPILATION_RUNNING);
    }

    public function setCompilationFailed(): void
    {
        $this->setIfCurrentState(Job::STATE_COMPILATION_FAILED);
    }

    public function setExecutionAwaiting(): void
    {
        $this->conditionallySetState(
            function (): bool {
                return
                    WorkflowInterface::STATE_COMPLETE === $this->compilationWorkflowFactory->create()->getState()
                    && in_array(
                        $this->executionWorkflowFactory->create()->getState(),
                        [
                            WorkflowInterface::STATE_NOT_STARTED,
                            WorkflowInterface::STATE_IN_PROGRESS,
                        ]
                    );
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
            function (Job $job): bool {
                return false === $job->isFinished()
                    && WorkflowInterface::STATE_COMPLETE === $this->executionWorkflowFactory->create()->getState();
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
     * @param Job::STATE_* $state
     */
    private function setIfCurrentState(string $state): void
    {
        $this->conditionallySetState(
            function (): bool {
                return true;
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
