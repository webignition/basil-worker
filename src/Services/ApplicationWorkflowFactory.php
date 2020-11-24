<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Workflow\ApplicationWorkflow;
use App\Model\Workflow\WorkflowInterface;

class ApplicationWorkflowFactory
{
    private CallbackWorkflowFactory $callbackWorkflowFactory;
    private CompilationStateFactory $compilationStateFactory;
    private ExecutionStateFactory $executionStateFactory;

    public function __construct(
        CallbackWorkflowFactory $callbackWorkflowFactory,
        CompilationStateFactory $compilationStateFactory,
        ExecutionStateFactory $executionStateFactory
    ) {
        $this->callbackWorkflowFactory = $callbackWorkflowFactory;
        $this->compilationStateFactory = $compilationStateFactory;
        $this->executionStateFactory = $executionStateFactory;
    }

    public function create(): ApplicationWorkflow
    {
        return new ApplicationWorkflow(
            WorkflowInterface::STATE_COMPLETE === $this->callbackWorkflowFactory->create()->getState(),
            $this->compilationStateFactory->create(),
            $this->executionStateFactory->create()
        );
    }
}
