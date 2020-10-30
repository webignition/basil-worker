<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\SourceCompileSuccessEvent;
use App\Services\CompilationWorkflowHandler;
use App\Services\ExecutionWorkflowHandler;
use App\Services\JobStateMutator;
use App\Services\TestStore;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SourceCompileSuccessEventSubscriber implements EventSubscriberInterface
{
    private TestStore $testStore;
    private CompilationWorkflowHandler $compilationWorkflowHandler;
    private JobStateMutator $jobStateMutator;
    private ExecutionWorkflowHandler $executionWorkflowHandler;

    public function __construct(
        TestStore $testStore,
        CompilationWorkflowHandler $compilationWorkflowHandler,
        JobStateMutator $jobStateMutator,
        ExecutionWorkflowHandler $executionWorkflowHandler
    ) {
        $this->testStore = $testStore;
        $this->compilationWorkflowHandler = $compilationWorkflowHandler;
        $this->jobStateMutator = $jobStateMutator;
        $this->executionWorkflowHandler = $executionWorkflowHandler;
    }

    public static function getSubscribedEvents()
    {
        return [
            SourceCompileSuccessEvent::NAME => [
                ['createTests', 10],
                ['dispatchNextCompileSourceMessage', 0],
                ['setJobStateToCompilationAwaitingIfCompilationComplete', 0],
                ['dispatchNextTestExecuteMessage', 0],
            ],
        ];
    }

    public function createTests(SourceCompileSuccessEvent $event): void
    {
        $suiteManifest = $event->getSuiteManifest();

        $this->testStore->createFromTestManifests($suiteManifest->getTestManifests());
    }

    public function dispatchNextCompileSourceMessage(): void
    {
        $this->compilationWorkflowHandler->dispatchNextCompileSourceMessage();
    }

    public function setJobStateToCompilationAwaitingIfCompilationComplete(): void
    {
        if ($this->compilationWorkflowHandler->isComplete()) {
            $this->jobStateMutator->setExecutionAwaiting();
        }
    }

    public function dispatchNextTestExecuteMessage(): void
    {
        if ($this->compilationWorkflowHandler->isComplete()) {
            $this->executionWorkflowHandler->dispatchNextExecuteTestMessage();
        }
    }
}
