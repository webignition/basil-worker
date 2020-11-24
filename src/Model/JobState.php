<?php

declare(strict_types=1);

namespace App\Model;

class JobState
{
    public const STATE_COMPILATION_FAILED = 'compilation-failed';
    public const STATE_EXECUTION_AWAITING = 'execution-awaiting';
    public const STATE_EXECUTION_RUNNING = 'execution-running';
    public const STATE_EXECUTION_FAILED = 'execution-failed';
    public const STATE_EXECUTION_COMPLETE = 'execution-complete';
    public const STATE_EXECUTION_CANCELLED = 'execution-cancelled';
    public const STATE_UNKNOWN = 'unknown';

    /**
     * @var JobState::STATE_*
     */
    private string $state;

    /**
     * @param JobState::STATE_* $state
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
