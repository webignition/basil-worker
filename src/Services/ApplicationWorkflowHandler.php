<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Workflow\WorkflowInterface;

class ApplicationWorkflowHandler
{
    private ApplicationWorkflowFactory $applicationWorkflowFactory;

    public function __construct(ApplicationWorkflowFactory $applicationWorkflowFactory)
    {
        $this->applicationWorkflowFactory = $applicationWorkflowFactory;
    }

    public function isComplete(): bool
    {
        return WorkflowInterface::STATE_COMPLETE === $this->applicationWorkflowFactory->create()->getState();
    }
}
