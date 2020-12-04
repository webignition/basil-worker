<?php

declare(strict_types=1);

namespace App\Services;

use App\Repository\CallbackRepository;
use App\Repository\SourceRepository;

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
    private CallbackRepository $callbackRepository;
    private SourceRepository $sourceRepository;

    public function __construct(
        JobStore $jobStore,
        CompilationState $compilationState,
        ExecutionState $executionState,
        CallbackState $callbackState,
        CallbackRepository $callbackRepository,
        SourceRepository $sourceRepository
    ) {
        $this->jobStore = $jobStore;
        $this->compilationState = $compilationState;
        $this->executionState = $executionState;
        $this->callbackState = $callbackState;
        $this->callbackRepository = $callbackRepository;
        $this->sourceRepository = $sourceRepository;
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
        if (false === $this->jobStore->hasJob()) {
            return self::STATE_AWAITING_JOB;
        }

        if (0 !== $this->callbackRepository->getJobTimeoutTypeCount()) {
            return self::STATE_TIMED_OUT;
        }

        if ([] === $this->sourceRepository->findAll()) {
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
