<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Job;

class JobStateMutator
{
    private JobStore $jobStore;

    public function __construct(JobStore $jobStore)
    {
        $this->jobStore = $jobStore;
    }

    public function setCompilationRunning(): void
    {
        $this->set(Job::STATE_COMPILATION_RUNNING);
    }

    public function setCompilationFailed(): void
    {
        $this->set(Job::STATE_COMPILATION_FAILED);
    }

    public function setExecutionAwaiting(): void
    {
        $this->set(Job::STATE_EXECUTION_AWAITING);
    }

    public function setExecutionRunning(): void
    {
        $this->set(Job::STATE_EXECUTION_RUNNING);
    }

    public function setExecutionComplete(): void
    {
        $this->set(Job::STATE_EXECUTION_COMPLETE);
    }

    public function setExecutionCancelled(): void
    {
        if ($this->jobStore->hasJob()) {
            $job = $this->jobStore->getJob();

            if (false === $job->isFinished()) {
                $job->setState(Job::STATE_EXECUTION_CANCELLED);
                $this->jobStore->store($job);
            }
        }
    }

    /**
     * @param Job::STATE_* $state
     */
    private function set(string $state): void
    {
        if ($this->jobStore->hasJob()) {
            $job = $this->jobStore->getJob();
            $job->setState($state);
            $this->jobStore->store($job);
        }
    }
}
