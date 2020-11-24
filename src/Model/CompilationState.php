<?php

declare(strict_types=1);

namespace App\Model;

class CompilationState
{
    public const STATE_AWAITING = 'awaiting';
    public const STATE_RUNNING = 'running';
    public const STATE_FAILED = 'failed';
    public const STATE_COMPLETE = 'complete';
    public const STATE_UNKNOWN = 'unknown';

    /**
     * @var CompilationState::STATE_*
     */
    private string $state;

    /**
     * @param CompilationState::STATE_* $state
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