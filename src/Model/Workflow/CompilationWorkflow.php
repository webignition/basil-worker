<?php

declare(strict_types=1);

namespace App\Model\Workflow;

class CompilationWorkflow
{
    public const STATE_NOT_READY = 'not-ready';
    public const STATE_NOT_STARTED = 'not-started';
    public const STATE_IN_PROGRESS = 'in-progress';
    public const STATE_COMPLETE = 'complete';

    /**
     * @var string[]
     */
    private array $compiledSources;
    private ?string $nextSource;

    /**
     * @param string[] $compiledSources
     * @param string|null $nextSource
     */
    public function __construct(array $compiledSources, ?string $nextSource)
    {
        $this->compiledSources = $compiledSources;
        $this->nextSource = $nextSource;
    }

    public function getState(): string
    {
        $hasCompiledSources = [] !== $this->compiledSources;
        $hasNextSource = is_string($this->nextSource);

        if (false === $hasCompiledSources && false === $hasNextSource) {
            return self::STATE_NOT_READY;
        }

        if (false === $hasCompiledSources && true === $hasNextSource) {
            return self::STATE_NOT_STARTED;
        }

        if (true === $hasCompiledSources && true === $hasNextSource) {
            return self::STATE_IN_PROGRESS;
        }

        return self::STATE_COMPLETE;
    }

    public function getNextSource(): ?string
    {
        return $this->nextSource;
    }
}
