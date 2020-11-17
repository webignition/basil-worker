<?php

declare(strict_types=1);

namespace App\Model\Workflow;

class CompilationWorkflow extends AbstractWorkflow
{
    private ?string $nextSource;

    /**
     * @param string[] $compiledSources
     * @param string|null $nextSource
     */
    public function __construct(array $compiledSources, ?string $nextSource)
    {
        parent::__construct($this->deriveState($compiledSources, $nextSource));

        $this->nextSource = $nextSource;
    }

    /**
     * @param string[] $compiledSources
     * @param string|null $nextSource
     *
     * @return WorkflowInterface::STATE_*
     */
    private function deriveState(array $compiledSources, ?string $nextSource): string
    {
        $hasCompiledSources = [] !== $compiledSources;
        $hasNextSource = is_string($nextSource);

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
