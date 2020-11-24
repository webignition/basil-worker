<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Test;
use App\Event\SourceCompile\SourceCompileSuccessEvent;
use App\Event\TestExecuteCompleteEvent;
use App\Message\ExecuteTest;
use App\Model\Workflow\WorkflowInterface;
use App\Repository\TestRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class ExecutionWorkflowHandler implements EventSubscriberInterface
{
    private MessageBusInterface $messageBus;
    private ExecutionWorkflowFactory $executionWorkflowFactory;
    private TestRepository $testRepository;
    private CompilationStateFactory $compilationStateFactory;

    public function __construct(
        MessageBusInterface $messageBus,
        ExecutionWorkflowFactory $executionWorkflowFactory,
        TestRepository $testRepository,
        CompilationStateFactory $compilationStateFactory
    ) {
        $this->messageBus = $messageBus;
        $this->executionWorkflowFactory = $executionWorkflowFactory;
        $this->testRepository = $testRepository;
        $this->compilationStateFactory = $compilationStateFactory;
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
        $compilationState = $this->compilationStateFactory->create();
        if (false === $compilationState->isFinished()) {
            return;
        }

        $isReadyToExecute = in_array(
            $this->executionWorkflowFactory->create()->getState(),
            [
                WorkflowInterface::STATE_NOT_STARTED,
                WorkflowInterface::STATE_IN_PROGRESS,
            ]
        );

        if (false === $isReadyToExecute) {
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
}
