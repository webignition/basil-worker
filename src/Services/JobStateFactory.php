<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\JobState;
use App\Model\Workflow\WorkflowInterface;
use App\Repository\CallbackRepository;
use App\Repository\TestRepository;

class JobStateFactory
{
    private JobStore $jobStore;
    private CompilationWorkflowFactory $compilationWorkflowFactory;
    private CallbackRepository $callbackRepository;
    private ExecutionWorkflowFactory $executionWorkflowFactory;
    private TestRepository $testRepository;

    public function __construct(
        JobStore $jobStore,
        CompilationWorkflowFactory $compilationWorkflowFactory,
        CallbackRepository $callbackRepository,
        ExecutionWorkflowFactory $executionWorkflowFactory,
        TestRepository $testRepository
    ) {
        $this->jobStore = $jobStore;
        $this->compilationWorkflowFactory = $compilationWorkflowFactory;
        $this->callbackRepository = $callbackRepository;
        $this->executionWorkflowFactory = $executionWorkflowFactory;
        $this->testRepository = $testRepository;
    }

    public function create(): JobState
    {
        foreach ($this->getJobStateDeciders() as $stateName => $decider) {
            if ($decider()) {
                return new JobState($stateName);
            }
        }

        return new JobState(JobState::STATE_UNKNOWN);
    }

    /**
     * @return array<JobState::STATE_*, callable>
     */
    private function getJobStateDeciders(): array
    {
        return [
            JobState::STATE_COMPILATION_AWAITING => function (): bool {
                if (false === $this->jobStore->hasJob()) {
                    return true;
                }

                return [] === $this->jobStore->getJob()->getSources();
            },
            JobState::STATE_COMPILATION_RUNNING => function (): bool {
                if (false === $this->jobStore->hasJob()) {
                    return false;
                }

                if (0 !== $this->callbackRepository->getCompileFailureTypeCount()) {
                    return false;
                }

                return WorkflowInterface::STATE_COMPLETE !== $this->compilationWorkflowFactory->create()->getState();
            },
            JobState::STATE_COMPILATION_FAILED => function (): bool {
                if (false === $this->jobStore->hasJob()) {
                    return false;
                }

                if ([] === $this->jobStore->getJob()->getSources()) {
                    return false;
                }

                return 0 !== $this->callbackRepository->getCompileFailureTypeCount();
            },
            JobState::STATE_EXECUTION_AWAITING => function (): bool {
                return
                    WorkflowInterface::STATE_COMPLETE == $this->compilationWorkflowFactory->create()->getState() &&
                    WorkflowInterface::STATE_NOT_STARTED === $this->executionWorkflowFactory->create()->getState();
            },
            JobState::STATE_EXECUTION_RUNNING => function (): bool {
                return WorkflowInterface::STATE_IN_PROGRESS === $this->executionWorkflowFactory->create()->getState();
            },
            JobState::STATE_EXECUTION_COMPLETE => function (): bool {
                return
                    WorkflowInterface::STATE_COMPLETE === $this->executionWorkflowFactory->create()->getState() &&
                    0 === $this->testRepository->getFailedCount() &&
                    0 === $this->testRepository->getCancelledCount();
            },
            JobState::STATE_EXECUTION_CANCELLED => function (): bool {
                return
                    0 !== $this->testRepository->getFailedCount() ||
                    0 !== $this->testRepository->getCancelledCount();
            },
        ];
    }
}
