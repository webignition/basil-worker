<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Workflow\ApplicationWorkflow;
use App\Model\Workflow\WorkflowInterface;

class ApplicationWorkflowFactory
{
    private JobStore $jobStore;
    private CallbackWorkflowFactory $callbackWorkflowFactory;

    public function __construct(JobStore $jobStore, CallbackWorkflowFactory $callbackWorkflowFactory)
    {
        $this->jobStore = $jobStore;
        $this->callbackWorkflowFactory = $callbackWorkflowFactory;
    }

    public function create(): ApplicationWorkflow
    {
        $job = $this->jobStore->hasJob()
            ? $this->jobStore->getJob()
            : null;

        return new ApplicationWorkflow(
            $job,
            WorkflowInterface::STATE_COMPLETE === $this->callbackWorkflowFactory->create()->getState()
        );
    }
}
