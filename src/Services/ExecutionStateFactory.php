<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Test;
use App\Model\ExecutionState;
use App\Repository\TestRepository;

class ExecutionStateFactory
{
    private TestRepository $testRepository;

    public function __construct(TestRepository $testRepository)
    {
        $this->testRepository = $testRepository;
    }

    /**
     * @param ExecutionState::STATE_* ...$states
     *
     * @return bool
     */
    public function is(...$states): bool
    {
        $states = array_filter($states, function ($item) {
            return is_string($item);
        });

        return in_array($this->getCurrentState(), $states);
    }

    /**
     * @return ExecutionState::STATE_*
     */
    public function getCurrentState(): string
    {
        $hasFailedTests = 0 !== $this->testRepository->count(['state' => Test::STATE_FAILED]);
        $hasCancelledTests = 0 !== $this->testRepository->count(['state' => Test::STATE_CANCELLED]);

        if ($hasFailedTests || $hasCancelledTests) {
            return ExecutionState::STATE_CANCELLED;
        }

        $hasFinishedTests = 0 !== $this->testRepository->count(['state' => Test::FINISHED_STATES]);
        $hasRunningTests = 0 !== $this->testRepository->count(['state' => Test::STATE_RUNNING]);
        $hasAwaitingTests = 0 !== $this->testRepository->count(['state' => Test::STATE_AWAITING]);

        if ($hasFinishedTests) {
            return $hasAwaitingTests || $hasRunningTests
                ? ExecutionState::STATE_RUNNING
                : ExecutionState::STATE_COMPLETE;
        }

        return $hasRunningTests ? ExecutionState::STATE_RUNNING : ExecutionState::STATE_AWAITING;
    }
}
