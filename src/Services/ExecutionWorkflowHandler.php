<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Test;
use App\Event\SourceCompile\SourceCompileSuccessEvent;
use App\Event\TestExecuteCompleteEvent;
use App\Message\ExecuteTest;
use App\Model\Workflow\ExecutionWorkflow;
use App\Repository\TestRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class ExecutionWorkflowHandler implements EventSubscriberInterface
{
    private MessageBusInterface $messageBus;
    private ExecutionWorkflowFactory $executionWorkflowFactory;
    private TestRepository $testRepository;
    private CompilationWorkflowHandler $compilationWorkflowHandler;

    public function __construct(
        MessageBusInterface $messageBus,
        ExecutionWorkflowFactory $executionWorkflowFactory,
        TestRepository $testRepository,
        CompilationWorkflowHandler $compilationWorkflowHandler
    ) {
        $this->messageBus = $messageBus;
        $this->executionWorkflowFactory = $executionWorkflowFactory;
        $this->testRepository = $testRepository;
        $this->compilationWorkflowHandler = $compilationWorkflowHandler;
    }

    public static function getSubscribedEvents()
    {
        return [
            SourceCompileSuccessEvent::class => [
                ['dispatchNextExecuteTestMessage', 0],
            ],
            TestExecuteCompleteEvent::class => [
                ['dispatchNextExecuteTestMessageFromTestExecuteCompleteEvent', 0],
            ],
        ];
    }

    public function dispatchNextExecuteTestMessageFromTestExecuteCompleteEvent(TestExecuteCompleteEvent $event): void
    {
        $test = $event->getTest();

        if (Test::STATE_COMPLETE === $test->getState()) {
            $this->dispatchNextExecuteTestMessage();
        }
    }

    public function dispatchNextExecuteTestMessage(): void
    {
        if (false === $this->compilationWorkflowHandler->isComplete()) {
            return;
        }

        if (false === $this->isReadyToExecute()) {
            return;
        }

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

        return in_array(
            $workflow->getState(),
            [
                ExecutionWorkflow::STATE_NOT_STARTED,
                ExecutionWorkflow::STATE_IN_PROGRESS,
            ]
        );
    }
}
