<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\ExecutionState;

class ApplicationState
{
    public const STATE_AWAITING_JOB = 'awaiting-job';
    public const STATE_AWAITING_SOURCES = 'awaiting-sources';
    public const STATE_COMPILING = 'compiling';
    public const STATE_EXECUTING = 'executing';
    public const STATE_COMPLETING_CALLBACKS = 'completing-callbacks';
    public const STATE_COMPLETE = 'complete';

    private JobStore $jobStore;
    private CompilationState $compilationState;
    private ExecutionStateFactory $executionStateFactory;
    private CallbackState $callbackState;

    public function __construct(
        JobStore $jobStore,
        CompilationState $compilationState,
        ExecutionStateFactory $executionStateFactory,
        CallbackState $callbackState
    ) {
        $this->jobStore = $jobStore;
        $this->compilationState = $compilationState;
        $this->executionStateFactory = $executionStateFactory;
        $this->callbackState = $callbackState;
    }

    /**
     * @param ApplicationState::STATE_* ...$states
     *
     * @return bool
     */
    public function is(...$states): bool
    {
        $states = array_filter($states, function ($item) {
            return is_string($item);
        });

        return in_array($this->getCurrentState(), $states);
    }

    private function getCurrentState(): string
    {
        if (false === $this->jobStore->hasJob()) {
            return self::STATE_AWAITING_JOB;
        }

        $job = $this->jobStore->getJob();
        if ([] === $job->getSources()) {
            return self::STATE_AWAITING_SOURCES;
        }

        if (false === $this->compilationState->is(...CompilationState::FINISHED_STATES)) {
            return self::STATE_COMPILING;
        }

        if (false === $this->executionStateFactory->is(...ExecutionState::FINISHED_STATES)) {
            return self::STATE_EXECUTING;
        }

        if (
            $this->callbackState->is(CallbackState::STATE_AWAITING, CallbackState::STATE_RUNNING)
        ) {
            return self::STATE_COMPLETING_CALLBACKS;
        }

        return self::STATE_COMPLETE;
    }
}
