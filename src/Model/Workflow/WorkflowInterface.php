<?php

declare(strict_types=1);

namespace App\Model\Workflow;

interface WorkflowInterface
{
    public const STATE_NOT_READY = 'not-ready';
    public const STATE_NOT_STARTED = 'not-started';
    public const STATE_IN_PROGRESS = 'in-progress';
    public const STATE_COMPLETE = 'complete';

    /**
     * @return WorkflowInterface::STATE_*
     */
    public function getState(): string;
}
