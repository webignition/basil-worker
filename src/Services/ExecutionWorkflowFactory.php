<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Test;
use App\Model\Workflow\ExecutionWorkflow;

class ExecutionWorkflowFactory
{
    private CompilationWorkflowFactory $compilationWorkflowFactory;
    private TestStore $testStore;

    public function __construct(
        CompilationWorkflowFactory $compilationWorkflowFactory,
        TestStore $testStore
    ) {
        $this->compilationWorkflowFactory = $compilationWorkflowFactory;
        $this->testStore = $testStore;
    }

    public function create(): ExecutionWorkflow
    {
        $compilationWorkflow = $this->compilationWorkflowFactory->create();
        $nextAwaitingTest = $this->testStore->findNextAwaiting();
        $nextTestId = $nextAwaitingTest instanceof Test ? $nextAwaitingTest->getId() : null;

        return new ExecutionWorkflow(
            $compilationWorkflow->getState(),
            $this->testStore->getTotalCount(),
            $this->testStore->getAwaitingCount(),
            $nextTestId
        );
    }
}
