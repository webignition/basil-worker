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
