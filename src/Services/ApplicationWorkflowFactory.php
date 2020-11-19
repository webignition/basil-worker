<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Workflow\ApplicationWorkflow;
use App\Model\Workflow\WorkflowInterface;

class ApplicationWorkflowFactory
{
    private CallbackWorkflowFactory $callbackWorkflowFactory;
    private JobStateFactory $jobStateFactory;

    public function __construct(CallbackWorkflowFactory $callbackWorkflowFactory, JobStateFactory $jobStateFactory)
    {
        $this->callbackWorkflowFactory = $callbackWorkflowFactory;
        $this->jobStateFactory = $jobStateFactory;
    }

    public function create(): ApplicationWorkflow
    {
        return new ApplicationWorkflow(
            $this->jobStateFactory->create(),
            WorkflowInterface::STATE_COMPLETE === $this->callbackWorkflowFactory->create()->getState()
        );
    }
}
