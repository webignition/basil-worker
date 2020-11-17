<?php

declare(strict_types=1);

namespace App\Model\Workflow;

use App\Entity\Job;

class ApplicationWorkflow
{
    public const STATE_NOT_READY = 'not-ready';
    public const STATE_NOT_STARTED = 'not-started';
    public const STATE_IN_PROGRESS = 'in-progress';
    public const STATE_COMPLETE = 'complete';

    private ?Job $job;
    private string $callbackWorkflowState;


    public function __construct(?Job $job, string $callbackWorkflowState)
    {
        $this->job = $job;
        $this->callbackWorkflowState = $callbackWorkflowState;
    }

    public function getState(): string
    {
        if (null === $this->job) {
            return self::STATE_NOT_READY;
        }

        $jobState = $this->job->getState();

        if (Job::STATE_COMPILATION_AWAITING === $jobState) {
            return self::STATE_NOT_STARTED;
        }

        if ($this->job->isRunning()) {
            return self::STATE_IN_PROGRESS;
        }

        if (Job::STATE_EXECUTION_CANCELLED === $jobState) {
            return self::STATE_COMPLETE;
        }

        return CallbackWorkflow::STATE_COMPLETE === $this->callbackWorkflowState
            ? self::STATE_COMPLETE
            : self::STATE_IN_PROGRESS;
    }
}
