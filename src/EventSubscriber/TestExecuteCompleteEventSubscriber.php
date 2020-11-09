<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Test;
use App\Event\JobCompletedEvent;
use App\Event\TestExecuteCompleteEvent;
use App\Services\ExecutionWorkflowHandler;
use App\Services\JobStore;
use App\Services\TestStateMutator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TestExecuteCompleteEventSubscriber implements EventSubscriberInterface
{
    private ExecutionWorkflowHandler $executionWorkflowHandler;
    private TestStateMutator $testStateMutator;
    private JobStore $jobStore;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        ExecutionWorkflowHandler $executionWorkflowHandler,
        TestStateMutator $testStateMutator,
        JobStore $jobStore,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->executionWorkflowHandler = $executionWorkflowHandler;
        $this->testStateMutator = $testStateMutator;
        $this->jobStore = $jobStore;
        $this->eventDispatcher = $eventDispatcher;
    }

    public static function getSubscribedEvents()
    {
        return [
            TestExecuteCompleteEvent::class => [
                ['setJobStateToExecutionCompleteIfTestFailed', 10],
                ['setTestStateToCompleteIfPassed', 10],
                ['dispatchNextTestExecuteMessageIfPassed', 0],
                ['setJobStateToExecutionCompleteIfAllTestsFinished', 0],
            ],
        ];
    }

    public function setJobStateToExecutionCompleteIfTestFailed(TestExecuteCompleteEvent $event): void
    {
        $test = $event->getTest();

        if (Test::STATE_FAILED === $test->getState()) {
            $this->eventDispatcher->dispatch(new JobCompletedEvent());
        }
    }

    public function setTestStateToCompleteIfPassed(TestExecuteCompleteEvent $event): void
    {
        $test = $event->getTest();

        if (Test::STATE_FAILED !== $test->getState()) {
            $this->testStateMutator->setComplete($test);
        }
    }

    public function dispatchNextTestExecuteMessageIfPassed(TestExecuteCompleteEvent $event): void
    {
        $test = $event->getTest();

        if (Test::STATE_COMPLETE === $test->getState()) {
            $this->executionWorkflowHandler->dispatchNextExecuteTestMessage();
        }
    }

    public function setJobStateToExecutionCompleteIfAllTestsFinished(): void
    {
        if ($this->executionWorkflowHandler->isComplete()) {
            $this->eventDispatcher->dispatch(new JobCompletedEvent());
        }
    }
}
