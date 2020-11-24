<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\JobState;
use App\Model\Workflow\WorkflowInterface;
use App\Repository\TestRepository;

class JobStateFactory
{
    private ExecutionWorkflowFactory $executionWorkflowFactory;
    private TestRepository $testRepository;

    public function __construct(ExecutionWorkflowFactory $executionWorkflowFactory, TestRepository $testRepository)
    {
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
