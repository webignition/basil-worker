<?php

declare(strict_types=1);

namespace App\Services;

class ApplicationStateFactory
{
    public const STATE_AWAITING_JOB = 'awaiting-job';
    public const STATE_AWAITING_SOURCES = 'awaiting-sources';
    public const STATE_COMPILING = 'compiling';
    public const STATE_EXECUTING = 'executing';
    public const STATE_COMPLETING_CALLBACKS = 'completing-callbacks';
    public const STATE_COMPLETE = 'complete';

    private JobStore $jobStore;
    private CompilationStateFactory $compilationStateFactory;
    private ExecutionStateFactory $executionStateFactory;
    private CallbackStateFactory $callbackStateFactory;

    public function __construct(
        JobStore $jobStore,
        CompilationStateFactory $compilationStateFactory,
        ExecutionStateFactory $executionStateFactory,
        CallbackStateFactory $callbackStateFactory
    ) {
        $this->jobStore = $jobStore;
        $this->compilationStateFactory = $compilationStateFactory;
        $this->executionStateFactory = $executionStateFactory;
        $this->callbackStateFactory = $callbackStateFactory;
    }

    /**
     * @param ApplicationStateFactory::STATE_* ...$states
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

        $compilationState = $this->compilationStateFactory->create();
        if (false === $compilationState->isFinished()) {
            return self::STATE_COMPILING;
        }

        $executionState = $this->executionStateFactory->create();
        if (false === $executionState->isFinished()) {
            return self::STATE_EXECUTING;
        }

        $callbackState = $this->callbackStateFactory->create();
        if (false === $callbackState->isFinished()) {
            return self::STATE_COMPLETING_CALLBACKS;
        }

        return self::STATE_COMPLETE;
    }
}
