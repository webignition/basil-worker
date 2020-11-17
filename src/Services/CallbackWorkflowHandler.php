<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Workflow\CallbackWorkflow;

class CallbackWorkflowHandler
{
    private CallbackWorkflowFactory $callbackWorkflowFactory;

    public function __construct(CallbackWorkflowFactory $callbackWorkflowFactory)
    {
        $this->callbackWorkflowFactory = $callbackWorkflowFactory;
    }

    public function isComplete(): bool
    {
        return CallbackWorkflow::STATE_COMPLETE === $this->callbackWorkflowFactory->create()->getState();
    }
}
