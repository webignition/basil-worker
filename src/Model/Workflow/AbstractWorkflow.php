<?php

declare(strict_types=1);

namespace App\Model\Workflow;

abstract class AbstractWorkflow implements WorkflowInterface
{
    /**
     * @var WorkflowInterface::STATE_*
     */
    private string $state;

    /**
     * @param WorkflowInterface::STATE_* $state
     */
    public function __construct(string $state)
    {
        $this->state = $state;
    }

    public function getState(): string
    {
        return $this->state;
    }
}
