<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Test;
use App\Event\TestExecuteCompleteEvent;
use App\Services\ExecutionWorkflowHandler;
use App\Services\JobStateMutator;
use App\Services\TestStateMutator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TestExecuteCompleteEventSubscriber implements EventSubscriberInterface
{
    private JobStateMutator $jobStateMutator;
    private ExecutionWorkflowHandler $executionWorkflowHandler;
    private TestStateMutator $testStateMutator;

    public function __construct(
        JobStateMutator $jobStateMutator,
        ExecutionWorkflowHandler $executionWorkflowHandler,
        TestStateMutator $testStateMutator
    ) {
        $this->jobStateMutator = $jobStateMutator;
        $this->executionWorkflowHandler = $executionWorkflowHandler;
        $this->testStateMutator = $testStateMutator;
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
            $this->jobStateMutator->setExecutionComplete();
        }
    }
}
