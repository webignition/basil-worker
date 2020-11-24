<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\JobState;
use App\Repository\TestRepository;

class JobStateFactory
{
    private TestRepository $testRepository;

    public function __construct(TestRepository $testRepository)
    {
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
            JobState::STATE_EXECUTION_CANCELLED => function (): bool {
                return
                    0 !== $this->testRepository->getFailedCount() ||
                    0 !== $this->testRepository->getCancelledCount();
            },
        ];
    }
}
