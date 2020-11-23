<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\CompilationState;
use App\Repository\CallbackRepository;

class CompilationStateFactory
{
    private CallbackRepository $callbackRepository;
    private JobSourceFinder $jobSourceFinder;

    public function __construct(CallbackRepository $callbackRepository, JobSourceFinder $jobSourceFinder)
    {
        $this->callbackRepository = $callbackRepository;
        $this->jobSourceFinder = $jobSourceFinder;
    }

    public function create(): CompilationState
    {
        if (0 !== $this->callbackRepository->getCompileFailureTypeCount()) {
            return new CompilationState(CompilationState::STATE_FAILED);
        }

        $compiledSources = $this->jobSourceFinder->findCompiledSources();
        $nextSource = $this->jobSourceFinder->findNextNonCompiledSource();

        if ([] === $compiledSources) {
            return is_string($nextSource)
                ? new CompilationState(CompilationState::STATE_RUNNING)
                : new CompilationState(CompilationState::STATE_AWAITING);
        }

        return is_string($nextSource)
            ? new CompilationState(CompilationState::STATE_RUNNING)
            : new CompilationState(CompilationState::STATE_COMPLETE);
    }
}
