<?php

declare(strict_types=1);

namespace App\Services;

use App\Repository\CallbackRepository;

class CompilationStateFactory
{
    public const STATE_AWAITING = 'awaiting';
    public const STATE_RUNNING = 'running';
    public const STATE_FAILED = 'failed';
    public const STATE_COMPLETE = 'complete';
    public const STATE_UNKNOWN = 'unknown';

    public const FINISHED_STATES = [
        self::STATE_COMPLETE,
        self::STATE_FAILED,
    ];

    private CallbackRepository $callbackRepository;
    private JobSourceFinder $jobSourceFinder;

    public function __construct(CallbackRepository $callbackRepository, JobSourceFinder $jobSourceFinder)
    {
        $this->callbackRepository = $callbackRepository;
        $this->jobSourceFinder = $jobSourceFinder;
    }

    /**
     * @param CompilationStateFactory::STATE_* ...$states
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

    /**
     * @return CompilationStateFactory::STATE_*
     */
    public function getCurrentState(): string
    {
        if (0 !== $this->callbackRepository->getCompileFailureTypeCount()) {
            return CompilationStateFactory::STATE_FAILED;
        }

        $compiledSources = $this->jobSourceFinder->findCompiledSources();
        $nextSource = $this->jobSourceFinder->findNextNonCompiledSource();

        if ([] === $compiledSources) {
            return is_string($nextSource)
                ? CompilationStateFactory::STATE_RUNNING
                : CompilationStateFactory::STATE_AWAITING;
        }

        return is_string($nextSource)
            ? CompilationStateFactory::STATE_RUNNING
            : CompilationStateFactory::STATE_COMPLETE;
    }
}
