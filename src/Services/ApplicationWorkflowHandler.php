<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Workflow\ApplicationWorkflow;

class ApplicationWorkflowHandler
{
    private ApplicationWorkflowFactory $applicationWorkflowFactory;

    public function __construct(ApplicationWorkflowFactory $applicationWorkflowFactory)
    {
        $this->applicationWorkflowFactory = $applicationWorkflowFactory;
    }

    public function isComplete(): bool
    {
        return ApplicationWorkflow::STATE_COMPLETE === $this->applicationWorkflowFactory->create()->getState();
    }
}
