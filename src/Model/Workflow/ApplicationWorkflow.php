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
    private bool $callbackWorkflowIsComplete;

    public function __construct(?Job $job, bool $callbackWorkflowIsComplete)
    {
        $this->job = $job;
        $this->callbackWorkflowIsComplete = $callbackWorkflowIsComplete;
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

        return $this->callbackWorkflowIsComplete
            ? self::STATE_COMPLETE
            : self::STATE_IN_PROGRESS;
    }
}
