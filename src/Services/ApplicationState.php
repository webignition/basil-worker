<?php

declare(strict_types=1);

namespace App\Services;

use webignition\BasilWorker\PersistenceBundle\Services\Store\CallbackStore;
use webignition\BasilWorker\PersistenceBundle\Services\Store\JobStore;
use webignition\BasilWorker\PersistenceBundle\Services\Store\SourceStore;

class ApplicationState
{
    public const STATE_AWAITING_JOB = 'awaiting-job';
    public const STATE_AWAITING_SOURCES = 'awaiting-sources';
    public const STATE_COMPILING = 'compiling';
    public const STATE_EXECUTING = 'executing';
    public const STATE_COMPLETING_CALLBACKS = 'completing-callbacks';
    public const STATE_COMPLETE = 'complete';
    public const STATE_TIMED_OUT = 'timed-out';

    private JobStore $jobStore;
    private CompilationState $compilationState;
    private ExecutionState $executionState;
    private CallbackState $callbackState;
    private CallbackStore $callbackStore;
    private SourceStore $sourceStore;

    public function __construct(
        JobStore $jobStore,
        CompilationState $compilationState,
        ExecutionState $executionState,
        CallbackState $callbackState,
        CallbackStore $callbackStore,
        SourceStore $sourceStore
    ) {
        $this->jobStore = $jobStore;
        $this->compilationState = $compilationState;
        $this->executionState = $executionState;
        $this->callbackState = $callbackState;
        $this->callbackStore = $callbackStore;
        $this->sourceStore = $sourceStore;
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

    public function getCurrentState(): string
    {
        if (false === $this->jobStore->has()) {
            return self::STATE_AWAITING_JOB;
        }

        if (0 !== $this->callbackStore->getJobTimeoutTypeCount()) {
            return self::STATE_TIMED_OUT;
        }

        if (false === $this->sourceStore->hasAny()) {
            return self::STATE_AWAITING_SOURCES;
        }

        if (false === $this->compilationState->is(...CompilationState::FINISHED_STATES)) {
            return self::STATE_COMPILING;
        }

        if (false === $this->executionState->is(...ExecutionState::FINISHED_STATES)) {
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
