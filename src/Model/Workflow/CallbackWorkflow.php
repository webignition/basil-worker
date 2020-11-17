<?php

declare(strict_types=1);

namespace App\Model\Workflow;

class CallbackWorkflow
{
    public const STATE_NOT_STARTED = 'not-started';
    public const STATE_IN_PROGRESS = 'in-progress';
    public const STATE_COMPLETE = 'complete';

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
            return self::STATE_NOT_STARTED;
        }

        return $this->finishedCallbackCount === $this->totalCallbackCount
            ? self::STATE_COMPLETE
            : self::STATE_IN_PROGRESS;
    }
}
