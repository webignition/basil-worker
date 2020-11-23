<?php

declare(strict_types=1);

namespace App\Model\Workflow;

class ExecutionWorkflow implements WorkflowInterface
{
    private bool $hasFinishedTests;
    private bool $hasRunningTests;
    private bool $hasAwaitingTests;

    private ?int $nextTestId;

    public function __construct(int $finishedTestCount, int $runningTestCount, int $awaitingTestCount, ?int $nextTestId)
    {
        $this->hasFinishedTests = $finishedTestCount > 0;
        $this->hasRunningTests = $runningTestCount > 0;
        $this->hasAwaitingTests = $awaitingTestCount > 0;
        $this->nextTestId = $nextTestId;
    }

// hasFinished  hasRunning  hasAwaiting not-ready
// 0            0           0           1               !hasFinished && !hasRunning && !hasAwaiting
// 0            0           1           0
// 0            1           0           0
// 1            0           0           0
// 0            1           1           0
// 1            0           1           0
// 1            1           0           0
// 1            1           1           0

// hasFinished  hasRunning  hasAwaiting not-started
// 0            0           0           0
// 0            0           1           1               !hasFinished && !hasRunning && hasAwaiting
// 0            1           0           0
// 1            0           0           0
// 0            1           1           0
// 1            0           1           0
// 1            1           0           0
// 1            1           1           0

// hasFinished  hasRunning  hasAwaiting in-progress
// 0            1           0           1
// 0            1           1           1
// 1            0           1           1
// 1            1           0           1
// 1            1           1           1

// hasFinished  hasRunning  hasAwaiting complete
// 0            0           0           0
// 0            0           1           0
// 0            1           0           0
// 1            0           0           1               hasFinished && !hasRunning && !hasAwaiting
// 0            1           1           0
// 1            0           1           0
// 1            1           0           0
// 1            1           1           0

// ===

// hasFinished  hasRunning  hasAwaiting state
// 0            0           0           not-ready
// 0            0           1           not-started
// 0            1           0           in-progress
// 1            0           0           complete
// 0            1           1           in-progress
// 1            0           1           in-progress
// 1            1           0           in-progress
// 1            1           1           in-progress

    public function getState(): string
    {
        if (!$this->hasFinishedTests && !$this->hasRunningTests && !$this->hasAwaitingTests) {
            return WorkflowInterface::STATE_NOT_READY;
        }

        if (!$this->hasFinishedTests && !$this->hasRunningTests && $this->hasAwaitingTests) {
            return WorkflowInterface::STATE_NOT_STARTED;
        }

        if ($this->hasFinishedTests && !$this->hasRunningTests && !$this->hasAwaitingTests) {
            return WorkflowInterface::STATE_COMPLETE;
        }

        return WorkflowInterface::STATE_IN_PROGRESS;
    }

    public function getNextTestId(): ?int
    {
        return $this->nextTestId;
    }
}
