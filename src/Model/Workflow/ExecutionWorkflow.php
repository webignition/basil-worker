<?php

declare(strict_types=1);

namespace App\Model\Workflow;

class ExecutionWorkflow
{
    public const STATE_NOT_READY = 'not-ready';
    public const STATE_NOT_STARTED = 'not-started';
    public const STATE_IN_PROGRESS = 'in-progress';
    public const STATE_COMPLETE = 'complete';

    private string $compilationWorkflowState;
    private int $totalTestCount;
    private int $awaitingTestCount;
    private ?int $nextTestId;

    public function __construct(
        string $compilationWorkflowState,
        int $totalTestCount,
        int $awaitingTestCount,
        ?int $nextTestId
    ) {
        $this->compilationWorkflowState = $compilationWorkflowState;
        $this->totalTestCount = $totalTestCount;
        $this->awaitingTestCount = $awaitingTestCount;
        $this->nextTestId = $nextTestId;
    }

    public function getState(): string
    {
        if (CompilationWorkflow::STATE_COMPLETE !== $this->compilationWorkflowState) {
            return self::STATE_NOT_READY;
        }

        if ($this->totalTestCount === $this->awaitingTestCount) {
            return self::STATE_NOT_STARTED;
        }

        if ($this->awaitingTestCount > 0) {
            return self::STATE_IN_PROGRESS;
        }

        return self::STATE_COMPLETE;
    }

    public function getNextTestId(): ?int
    {
        return $this->nextTestId;
    }
}
