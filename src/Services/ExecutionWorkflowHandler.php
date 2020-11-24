<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Test;
use App\Event\SourceCompile\SourceCompileSuccessEvent;
use App\Event\TestExecuteCompleteEvent;
use App\Message\ExecuteTest;
use App\Repository\TestRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class ExecutionWorkflowHandler implements EventSubscriberInterface
{
    private MessageBusInterface $messageBus;
    private TestRepository $testRepository;
    private CompilationStateFactory $compilationStateFactory;
    private ExecutionStateFactory $executionStateFactory;

    public function __construct(
        MessageBusInterface $messageBus,
        TestRepository $testRepository,
        CompilationStateFactory $compilationStateFactory,
        ExecutionStateFactory $executionStateFactory
    ) {
        $this->messageBus = $messageBus;
        $this->testRepository = $testRepository;
        $this->compilationStateFactory = $compilationStateFactory;
        $this->executionStateFactory = $executionStateFactory;
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
        if (false === $this->compilationStateFactory->is(...CompilationStateFactory::FINISHED_STATES)) {
            return;
        }

        $executionState = $this->executionStateFactory->create();
        if ($executionState->isFinished()) {
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
