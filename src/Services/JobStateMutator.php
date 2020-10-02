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

    public function setCompilationFailed(): void
    {
        if ($this->jobStore->hasJob()) {
            $job = $this->jobStore->getJob();
            $job->setState(Job::STATE_COMPILATION_FAILED);
            $this->jobStore->store();
        }
    }
}
