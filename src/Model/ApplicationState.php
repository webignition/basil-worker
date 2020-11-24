<?php

declare(strict_types=1);

namespace App\Model;

class ApplicationState
{
    public const STATE_AWAITING = 'awaiting';
    public const STATE_COMPILING = 'compiling';
    public const STATE_EXECUTING = 'executing';
    public const STATE_COMPLETING_CALLBACKS = 'completing-callbacks';
    public const STATE_COMPLETE = 'complete';

    private const FINISHED_STATES = [
        self::STATE_COMPLETE,
    ];

    /**
     * @var ApplicationState::STATE_*
     */
    private string $state;

    /**
     * @param ApplicationState::STATE_* $state
     */
    public function __construct(string $state)
    {
        $this->state = $state;
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
