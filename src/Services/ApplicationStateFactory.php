<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\ApplicationState;

class ApplicationStateFactory
{
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
            return ApplicationState::STATE_AWAITING_JOB;
        }

        $job = $this->jobStore->getJob();
        if ([] === $job->getSources()) {
            return ApplicationState::STATE_AWAITING_SOURCES;
        }

        $compilationState = $this->compilationStateFactory->create();
        if (false === $compilationState->isFinished()) {
            return ApplicationState::STATE_COMPILING;
        }

        $executionState = $this->executionStateFactory->create();
        if (false === $executionState->isFinished()) {
            return ApplicationState::STATE_EXECUTING;
        }

        $callbackState = $this->callbackStateFactory->create();
        if (false === $callbackState->isFinished()) {
            return ApplicationState::STATE_COMPLETING_CALLBACKS;
        }

        return ApplicationState::STATE_COMPLETE;
    }
}
