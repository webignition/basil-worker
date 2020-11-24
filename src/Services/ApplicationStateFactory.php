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

    public function create(): ApplicationState
    {
        if (false === $this->jobStore->hasJob()) {
            return new ApplicationState(ApplicationState::STATE_AWAITING_JOB);
        }

        $job = $this->jobStore->getJob();
        if ([] === $job->getSources()) {
            return new ApplicationState(ApplicationState::STATE_AWAITING_SOURCES);
        }

        $compilationState = $this->compilationStateFactory->create();
        if (false === $compilationState->isFinished()) {
            return new ApplicationState(ApplicationState::STATE_COMPILING);
        }

        $executionState = $this->executionStateFactory->create();
        if (false === $executionState->isFinished()) {
            return new ApplicationState(ApplicationState::STATE_EXECUTING);
        }

        $callbackState = $this->callbackStateFactory->create();
        if (false === $callbackState->isFinished()) {
            return new ApplicationState(ApplicationState::STATE_COMPLETING_CALLBACKS);
        }

        return new ApplicationState(ApplicationState::STATE_COMPLETE);
    }
}
