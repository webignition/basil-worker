<?php

declare(strict_types=1);

namespace App\Model\Workflow;

class ExecutionWorkflow extends AbstractWorkflow
{
    private ?int $nextTestId;

    public function __construct(
        string $compilationWorkflowState,
        int $totalTestCount,
        int $awaitingTestCount,
        ?int $nextTestId
    ) {
        parent::__construct($this->deriveState($compilationWorkflowState, $totalTestCount, $awaitingTestCount));

        $this->nextTestId = $nextTestId;
    }

    /**
     * @return WorkflowInterface::STATE_*
     */
    private function deriveState(string $compilationWorkflowState, int $totalTestCount, int $awaitingTestCount): string
    {
        if (WorkflowInterface::STATE_COMPLETE !== $compilationWorkflowState) {
            return WorkflowInterface::STATE_NOT_READY;
        }

        if ($totalTestCount === $awaitingTestCount) {
            return WorkflowInterface::STATE_NOT_STARTED;
        }

        if ($awaitingTestCount > 0) {
            return WorkflowInterface::STATE_IN_PROGRESS;
        }

        return WorkflowInterface::STATE_COMPLETE;
    }

    public function getNextTestId(): ?int
    {
        return $this->nextTestId;
    }
}
