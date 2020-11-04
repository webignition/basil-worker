<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Test;
use App\Message\ExecuteTest;
use App\Model\Workflow\ExecutionWorkflow;
use App\Repository\TestRepository;
use Symfony\Component\Messenger\MessageBusInterface;

class ExecutionWorkflowHandler
{
    private MessageBusInterface $messageBus;
    private ExecutionWorkflowFactory $executionWorkflowFactory;
    private TestRepository $testRepository;

    public function __construct(
        MessageBusInterface $messageBus,
        ExecutionWorkflowFactory $executionWorkflowFactory,
        TestRepository $testRepository
    ) {
        $this->messageBus = $messageBus;
        $this->executionWorkflowFactory = $executionWorkflowFactory;
        $this->testRepository = $testRepository;
    }

    public function dispatchNextExecuteTestMessage(): void
    {
        $nextAwaitingTest = $this->testRepository->findNextAwaiting();

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
