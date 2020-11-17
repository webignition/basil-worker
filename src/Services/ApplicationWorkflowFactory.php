<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Workflow\ApplicationWorkflow;

class ApplicationWorkflowFactory
{
    private JobStore $jobStore;
    private CallbackWorkflowHandler $callbackWorkflowHandler;

    public function __construct(JobStore $jobStore, CallbackWorkflowHandler $callbackWorkflowHandler)
    {
        $this->jobStore = $jobStore;
        $this->callbackWorkflowHandler = $callbackWorkflowHandler;
    }

    public function create(): ApplicationWorkflow
    {
        $job = $this->jobStore->hasJob()
            ? $this->jobStore->getJob()
            : null;

        return new ApplicationWorkflow($job, $this->callbackWorkflowHandler->isComplete());
    }
}
