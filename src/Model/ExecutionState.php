<?php

declare(strict_types=1);

namespace App\Model;

class ExecutionState
{
    public const STATE_AWAITING = 'awaiting';
    public const STATE_RUNNING = 'running';
    public const STATE_COMPLETE = 'complete';
    public const STATE_CANCELLED = 'cancelled';
    /**
     * @var ExecutionState::STATE_*
     */
    private string $state;

    /**
     * @param ExecutionState::STATE_* $state
     */
    public function __construct(string $state)
    {
        $this->state = $state;
    }

    public function __toString(): string
    {
        return $this->state;
    }
}
