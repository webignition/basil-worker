<?php

declare(strict_types=1);

namespace App\Model\Workflow;

class CallbackWorkflow implements WorkflowInterface
{
    private int $totalCallbackCount;
    private int $finishedCallbackCount;

    public function __construct(int $totalCallbackCount, int $finishedCallbackCount)
    {
        $this->totalCallbackCount = $totalCallbackCount;
        $this->finishedCallbackCount = $finishedCallbackCount;
    }

    public function getState(): string
    {
        if (0 === $this->totalCallbackCount) {
            return WorkflowInterface::STATE_NOT_STARTED;
        }

        return $this->finishedCallbackCount === $this->totalCallbackCount
            ? WorkflowInterface::STATE_COMPLETE
            : WorkflowInterface::STATE_IN_PROGRESS;
    }
}
