<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Workflow\WorkflowInterface;

class CallbackWorkflowHandler
{
    private CallbackWorkflowFactory $callbackWorkflowFactory;

    public function __construct(CallbackWorkflowFactory $callbackWorkflowFactory)
    {
        $this->callbackWorkflowFactory = $callbackWorkflowFactory;
    }

    public function isComplete(): bool
    {
        return WorkflowInterface::STATE_COMPLETE === $this->callbackWorkflowFactory->create()->getState();
    }
}
