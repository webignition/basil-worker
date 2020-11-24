<?php

declare(strict_types=1);

namespace App\Model\Workflow;

class ExecutionWorkflow
{
    private ?int $nextTestId;

    public function __construct(?int $nextTestId)
    {
        $this->nextTestId = $nextTestId;
    }

    public function getNextTestId(): ?int
    {
        return $this->nextTestId;
    }
}
