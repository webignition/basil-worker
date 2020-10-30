<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Workflow\CompilationWorkflow;

class CompilationWorkflowFactory
{
    private JobSourceFinder $jobSourceFinder;

    public function __construct(JobSourceFinder $jobSourceFinder)
    {
        $this->jobSourceFinder = $jobSourceFinder;
    }

    public function create(): CompilationWorkflow
    {
        return new CompilationWorkflow(
            $this->jobSourceFinder->findCompiledSources(),
            $this->jobSourceFinder->findNextNonCompiledSource()
        );
    }
}
