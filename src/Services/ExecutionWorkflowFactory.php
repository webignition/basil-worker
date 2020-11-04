<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Test;
use App\Model\Workflow\ExecutionWorkflow;
use App\Repository\TestRepository;

class ExecutionWorkflowFactory
{
    private CompilationWorkflowFactory $compilationWorkflowFactory;
    private TestRepository $testRepository;

    public function __construct(CompilationWorkflowFactory $compilationWorkflowFactory, TestRepository $testRepository)
    {
        $this->compilationWorkflowFactory = $compilationWorkflowFactory;
        $this->testRepository = $testRepository;
    }

    public function create(): ExecutionWorkflow
    {
        $compilationWorkflow = $this->compilationWorkflowFactory->create();
        $nextAwaitingTest = $this->testRepository->findNextAwaiting();
        $nextTestId = $nextAwaitingTest instanceof Test ? $nextAwaitingTest->getId() : null;

        return new ExecutionWorkflow(
            $compilationWorkflow->getState(),
            $this->testRepository->count([]),
            $this->testRepository->getAwaitingCount(),
            $nextTestId
        );
    }
}
