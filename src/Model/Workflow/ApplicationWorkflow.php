<?php

declare(strict_types=1);

namespace App\Model\Workflow;

use App\Model\CompilationState;
use App\Model\ExecutionState;

class ApplicationWorkflow implements WorkflowInterface
{
    private bool $callbackWorkflowIsComplete;
    private CompilationState $compilationState;
    private ExecutionState $executionState;

    public function __construct(
        bool $callbackWorkflowIsComplete,
        CompilationState $compilationState,
        ExecutionState $executionState
    ) {
        $this->callbackWorkflowIsComplete = $callbackWorkflowIsComplete;
        $this->compilationState = $compilationState;
        $this->executionState = $executionState;
    }

    public function getState(): string
    {
        if (CompilationState::STATE_AWAITING === (string) $this->compilationState) {
            return WorkflowInterface::STATE_NOT_STARTED;
        }

        if (
            CompilationState::STATE_RUNNING === (string) $this->compilationState ||
            ExecutionState::STATE_RUNNING === (string) $this->executionState
        ) {
            return WorkflowInterface::STATE_IN_PROGRESS;
        }

        if (ExecutionState::STATE_CANCELLED === (string) $this->executionState) {
            return WorkflowInterface::STATE_COMPLETE;
        }

        return $this->callbackWorkflowIsComplete
            ? WorkflowInterface::STATE_COMPLETE
            : WorkflowInterface::STATE_IN_PROGRESS;
    }
}
