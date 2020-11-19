<?php

declare(strict_types=1);

namespace App\Model\Workflow;

class CompilationWorkflow implements WorkflowInterface
{
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
            return WorkflowInterface::STATE_NOT_READY;
        }

        if (false === $hasCompiledSources && true === $hasNextSource) {
            return WorkflowInterface::STATE_NOT_STARTED;
        }

        if (true === $hasCompiledSources && true === $hasNextSource) {
            return WorkflowInterface::STATE_IN_PROGRESS;
        }

        return WorkflowInterface::STATE_COMPLETE;
    }

    public function getNextSource(): ?string
    {
        return $this->nextSource;
    }
}
