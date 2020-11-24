<?php

declare(strict_types=1);

namespace App\Model;

class CallbackState
{
    public const STATE_AWAITING = 'awaiting';
    public const STATE_RUNNING = 'running';
    public const STATE_COMPLETE = 'complete';

    /**
     * @var CallbackState::STATE_*
     */
    private string $state;

    /**
     * @param CallbackState::STATE_* $state
     */
    public function __construct(string $state)
    {
        $this->state = $state;
    }

    public function isFinished(): bool
    {
        return self::STATE_COMPLETE === $this->state;
    }

    public function __toString(): string
    {
        return $this->state;
    }
}
