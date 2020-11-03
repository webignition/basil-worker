<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Test;
use App\Message\ExecuteTest;
use App\Model\Workflow\ExecutionWorkflow;
use Symfony\Component\Messenger\MessageBusInterface;

class ExecutionWorkflowHandler
{
    private TestStore $testStore;
    private MessageBusInterface $messageBus;
    private ExecutionWorkflowFactory $executionWorkflowFactory;

    public function __construct(
        TestStore $testStore,
        MessageBusInterface $messageBus,
        ExecutionWorkflowFactory $executionWorkflowFactory
    ) {
        $this->testStore = $testStore;
        $this->messageBus = $messageBus;
        $this->executionWorkflowFactory = $executionWorkflowFactory;
    }

    public function dispatchNextExecuteTestMessage(): void
    {
        $nextAwaitingTest = $this->testStore->findNextAwaiting();

        if ($nextAwaitingTest instanceof Test) {
            $testId = $nextAwaitingTest->getId();

            if (is_int($testId)) {
                $message = new ExecuteTest($testId);
                $this->messageBus->dispatch($message);
            }
        }
    }

    public function isComplete(): bool
    {
        $workflow = $this->executionWorkflowFactory->create();

        return ExecutionWorkflow::STATE_COMPLETE === $workflow->getState();
    }

    public function isReadyToExecute(): bool
    {
        $workflow = $this->executionWorkflowFactory->create();

        return ExecutionWorkflow::STATE_NOT_STARTED === $workflow->getState();
    }
}
