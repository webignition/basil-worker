<?php

declare(strict_types=1);

namespace App\Model\Workflow;

class CallbackWorkflow extends AbstractWorkflow
{
    public function __construct(int $totalCallbackCount, int $finishedCallbackCount)
    {
        parent::__construct($this->deriveState($totalCallbackCount, $finishedCallbackCount));
    }

    /**
     * @return WorkflowInterface::STATE_*
     */
    private function deriveState(int $totalCallbackCount, int $finishedCallbackCount): string
    {
        if (0 === $totalCallbackCount) {
            return WorkflowInterface::STATE_NOT_STARTED;
        }

        return $finishedCallbackCount === $totalCallbackCount
            ? WorkflowInterface::STATE_COMPLETE
            : WorkflowInterface::STATE_IN_PROGRESS;
    }
}
