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

    public function create(): ExecutionState
    {
        $hasFailedTests = 0 !== $this->testRepository->count(['state' => Test::STATE_FAILED]);
        $hasCancelledTests = 0 !== $this->testRepository->count(['state' => Test::STATE_CANCELLED]);

        if ($hasFailedTests || $hasCancelledTests) {
            return new ExecutionState(ExecutionState::STATE_CANCELLED);
        }

        $hasFinishedTests = 0 !== $this->testRepository->count(['state' => Test::FINISHED_STATES]);
        $hasRunningTests = 0 !== $this->testRepository->count(['state' => Test::STATE_RUNNING]);
        $hasAwaitingTests = 0 !== $this->testRepository->count(['state' => Test::STATE_AWAITING]);

        if ($hasFinishedTests) {
            $state = $hasAwaitingTests || $hasRunningTests
                ? ExecutionState::STATE_RUNNING
                : ExecutionState::STATE_COMPLETE;

            return new ExecutionState($state);
        }

        $state = $hasRunningTests ? ExecutionState::STATE_RUNNING : ExecutionState::STATE_AWAITING;

        return new ExecutionState($state);
    }
}
