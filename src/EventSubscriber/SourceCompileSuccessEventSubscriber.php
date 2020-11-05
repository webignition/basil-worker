<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\SourceCompileSuccessEvent;
use App\Services\CompilationWorkflowHandler;
use App\Services\ExecutionWorkflowHandler;
use App\Services\JobStateMutator;
use App\Services\TestFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SourceCompileSuccessEventSubscriber implements EventSubscriberInterface
{
    private TestFactory $testFactory;
    private CompilationWorkflowHandler $compilationWorkflowHandler;
    private JobStateMutator $jobStateMutator;
    private ExecutionWorkflowHandler $executionWorkflowHandler;

    public function __construct(
        TestFactory $testFactory,
        CompilationWorkflowHandler $compilationWorkflowHandler,
        JobStateMutator $jobStateMutator,
        ExecutionWorkflowHandler $executionWorkflowHandler
    ) {
        $this->testFactory = $testFactory;
        $this->compilationWorkflowHandler = $compilationWorkflowHandler;
        $this->jobStateMutator = $jobStateMutator;
        $this->executionWorkflowHandler = $executionWorkflowHandler;
    }

    public static function getSubscribedEvents()
    {
        return [
            SourceCompileSuccessEvent::class => [
                ['createTests', 30],
                ['dispatchNextCompileSourceMessage', 20],
                ['setJobStateToExecutionAwaitingIfCompilationComplete', 10],
                ['dispatchNextTestExecuteMessage', 0],
            ],
        ];
    }

    public function createTests(SourceCompileSuccessEvent $event): void
    {
        $suiteManifest = $event->getSuiteManifest();

        $this->testFactory->createFromManifestCollection($suiteManifest->getTestManifests());
    }

    public function dispatchNextCompileSourceMessage(): void
    {
        $this->compilationWorkflowHandler->dispatchNextCompileSourceMessage();
    }

    public function setJobStateToExecutionAwaitingIfCompilationComplete(): void
    {
        if ($this->compilationWorkflowHandler->isComplete() && $this->executionWorkflowHandler->isReadyToExecute()) {
            $this->jobStateMutator->setExecutionAwaiting();
        }
    }

    public function dispatchNextTestExecuteMessage(): void
    {
        if ($this->compilationWorkflowHandler->isComplete() && $this->executionWorkflowHandler->isReadyToExecute()) {
            $this->executionWorkflowHandler->dispatchNextExecuteTestMessage();
        }
    }
}
