<?php

declare(strict_types=1);

namespace App\Model\Workflow;

use App\Model\CompilationState;
use App\Model\JobState;

class ApplicationWorkflow implements WorkflowInterface
{
    private JobState $jobState;
    private bool $callbackWorkflowIsComplete;
    private CompilationState $compilationState;

    public function __construct(
        JobState $jobState,
        bool $callbackWorkflowIsComplete,
        CompilationState $compilationState
    ) {
        $this->jobState = $jobState;
        $this->callbackWorkflowIsComplete = $callbackWorkflowIsComplete;
        $this->compilationState = $compilationState;
    }

    public function getState(): string
    {
        if (CompilationState::STATE_AWAITING === (string) $this->compilationState) {
            return WorkflowInterface::STATE_NOT_STARTED;
        }

        if (
            in_array((string) $this->jobState, [JobState::STATE_COMPILATION_RUNNING, JobState::STATE_EXECUTION_RUNNING])
        ) {
            return WorkflowInterface::STATE_IN_PROGRESS;
        }

        if (JobState::STATE_EXECUTION_CANCELLED === (string) $this->jobState) {
            return WorkflowInterface::STATE_COMPLETE;
        }

        return $this->callbackWorkflowIsComplete
            ? WorkflowInterface::STATE_COMPLETE
            : WorkflowInterface::STATE_IN_PROGRESS;
    }
}
