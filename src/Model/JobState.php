<?php

declare(strict_types=1);

namespace App\Model;

class JobState
{
    public const STATE_COMPILATION_AWAITING = 'compilation-awaiting';
    public const STATE_COMPILATION_RUNNING = 'compilation-running';
    public const STATE_COMPILATION_FAILED = 'compilation-failed';
    public const STATE_EXECUTION_AWAITING = 'execution-awaiting';
    public const STATE_EXECUTION_RUNNING = 'execution-running';
    public const STATE_EXECUTION_FAILED = 'execution-failed';
    public const STATE_EXECUTION_COMPLETE = 'execution-complete';
    public const STATE_EXECUTION_CANCELLED = 'execution-cancelled';
    public const STATE_UNKNOWN = 'unknown';

    private const RUNNING_STATES = [
        self::STATE_COMPILATION_RUNNING,
        self::STATE_EXECUTION_RUNNING,
    ];

    private const FINISHED_STATES = [
        self::STATE_COMPILATION_FAILED,
        self::STATE_EXECUTION_FAILED,
        self::STATE_EXECUTION_COMPLETE,
        self::STATE_EXECUTION_CANCELLED,
    ];

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

    public function isRunning(): bool
    {
        return in_array($this->state, self::RUNNING_STATES);
    }

    public function isFinished(): bool
    {
        return in_array($this->state, self::FINISHED_STATES);
    }

    public function __toString(): string
    {
        return $this->state;
    }
}
