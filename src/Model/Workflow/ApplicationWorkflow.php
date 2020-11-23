<?php

declare(strict_types=1);

namespace App\Model\Workflow;

use App\Model\JobState;

class ApplicationWorkflow implements WorkflowInterface
{
    private JobState $jobState;
    private bool $callbackWorkflowIsComplete;

    public function __construct(JobState $jobState, bool $callbackWorkflowIsComplete)
    {
        $this->jobState = $jobState;
        $this->callbackWorkflowIsComplete = $callbackWorkflowIsComplete;
    }

    public function getState(): string
    {
        if (JobState::STATE_UNKNOWN === (string) $this->jobState) {
            return WorkflowInterface::STATE_NOT_READY;
        }

        if (JobState::STATE_COMPILATION_AWAITING === (string) $this->jobState) {
            return WorkflowInterface::STATE_NOT_STARTED;
        }

        if ($this->jobState->isRunning()) {
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
