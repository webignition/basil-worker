<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Test;
use App\Model\Workflow\ExecutionWorkflow;
use App\Repository\TestRepository;

class ExecutionWorkflowFactory
{
    private TestRepository $testRepository;

    public function __construct(TestRepository $testRepository)
    {
        $this->testRepository = $testRepository;
    }

    public function create(): ExecutionWorkflow
    {
        $nextAwaitingTest = $this->testRepository->findNextAwaiting();
        $nextTestId = $nextAwaitingTest instanceof Test ? $nextAwaitingTest->getId() : null;

        return new ExecutionWorkflow($nextTestId);
    }
}
