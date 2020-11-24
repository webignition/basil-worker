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
    private CompilationState $compilationState;
    private ExecutionStateFactory $executionStateFactory;

    public function __construct(
        MessageBusInterface $messageBus,
        TestRepository $testRepository,
        CompilationState $compilationState,
        ExecutionStateFactory $executionStateFactory
    ) {
        $this->messageBus = $messageBus;
        $this->testRepository = $testRepository;
        $this->compilationState = $compilationState;
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
        if (false === $this->compilationState->is(...CompilationState::FINISHED_STATES)) {
            return;
        }

        if ($this->executionStateFactory->is(...ExecutionStateFactory::FINISHED_STATES)) {
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
