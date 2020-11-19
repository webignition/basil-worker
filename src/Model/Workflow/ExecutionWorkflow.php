<?php

declare(strict_types=1);

namespace App\Model\Workflow;

class ExecutionWorkflow implements WorkflowInterface
{
    private string $compilationWorkflowState;
    private int $totalTestCount;
    private int $awaitingTestCount;
    private ?int $nextTestId;

    public function __construct(
        string $compilationWorkflowState,
        int $totalTestCount,
        int $awaitingTestCount,
        ?int $nextTestId
    ) {
        $this->compilationWorkflowState = $compilationWorkflowState;
        $this->totalTestCount = $totalTestCount;
        $this->awaitingTestCount = $awaitingTestCount;
        $this->nextTestId = $nextTestId;
    }

    public function getState(): string
    {
        if (WorkflowInterface::STATE_COMPLETE !== $this->compilationWorkflowState) {
            return WorkflowInterface::STATE_NOT_READY;
        }

        if ($this->totalTestCount === $this->awaitingTestCount) {
            return WorkflowInterface::STATE_NOT_STARTED;
        }

        if ($this->awaitingTestCount > 0) {
            return WorkflowInterface::STATE_IN_PROGRESS;
        }

        return WorkflowInterface::STATE_COMPLETE;
    }

    public function getNextTestId(): ?int
    {
        return $this->nextTestId;
    }
}
