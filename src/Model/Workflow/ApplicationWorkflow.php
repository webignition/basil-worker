<?php

declare(strict_types=1);

namespace App\Model\Workflow;

use App\Entity\Job;

class ApplicationWorkflow implements WorkflowInterface
{
    private ?Job $job;
    private bool $callbackWorkflowIsComplete;

    public function __construct(?Job $job, bool $callbackWorkflowIsComplete)
    {
        $this->job = $job;
        $this->callbackWorkflowIsComplete = $callbackWorkflowIsComplete;
    }

    public function getState(): string
    {
        if (null === $this->job) {
            return WorkflowInterface::STATE_NOT_READY;
        }

        $jobState = $this->job->getState();

        if (Job::STATE_COMPILATION_AWAITING === $jobState) {
            return WorkflowInterface::STATE_NOT_STARTED;
        }

        if ($this->job->isRunning()) {
            return WorkflowInterface::STATE_IN_PROGRESS;
        }

        if (Job::STATE_EXECUTION_CANCELLED === $jobState) {
            return WorkflowInterface::STATE_COMPLETE;
        }

        return $this->callbackWorkflowIsComplete
            ? WorkflowInterface::STATE_COMPLETE
            : WorkflowInterface::STATE_IN_PROGRESS;
    }
}
