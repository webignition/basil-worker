<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Test;
use App\Event\TestExecuteCompleteEvent;
use App\Services\ExecutionWorkflowHandler;
use App\Services\JobStateMutator;
use App\Services\TestStore;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TestExecuteCompleteEventSubscriber implements EventSubscriberInterface
{
    private JobStateMutator $jobStateMutator;
    private TestStore $testStore;
    private ExecutionWorkflowHandler $executionWorkflowHandler;

    public function __construct(
        JobStateMutator $jobStateMutator,
        TestStore $testStore,
        ExecutionWorkflowHandler $executionWorkflowHandler
    ) {
        $this->jobStateMutator = $jobStateMutator;
        $this->testStore = $testStore;
        $this->executionWorkflowHandler = $executionWorkflowHandler;
    }

    public static function getSubscribedEvents()
    {
        return [
            TestExecuteCompleteEvent::NAME => [
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
            $this->jobStateMutator->setExecutionComplete();
        }
    }

    public function setTestStateToCompleteIfPassed(TestExecuteCompleteEvent $event): void
    {
        $test = $event->getTest();

        if (Test::STATE_FAILED !== $test->getState()) {
            $test->setState(Test::STATE_COMPLETE);
            $this->testStore->store($test);
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
            $this->jobStateMutator->setExecutionComplete();
        }
    }
}
