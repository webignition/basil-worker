<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\SourceCompile\SourceCompileSuccessEvent;
use App\Services\CompilationWorkflowHandler;
use App\Services\ExecutionWorkflowHandler;
use App\Services\TestFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SourceCompileSuccessEventSubscriber implements EventSubscriberInterface
{
    private TestFactory $testFactory;
    private CompilationWorkflowHandler $compilationWorkflowHandler;
    private ExecutionWorkflowHandler $executionWorkflowHandler;

    public function __construct(
        TestFactory $testFactory,
        CompilationWorkflowHandler $compilationWorkflowHandler,
        ExecutionWorkflowHandler $executionWorkflowHandler
    ) {
        $this->testFactory = $testFactory;
        $this->compilationWorkflowHandler = $compilationWorkflowHandler;
        $this->executionWorkflowHandler = $executionWorkflowHandler;
    }

    public static function getSubscribedEvents()
    {
        return [
            SourceCompileSuccessEvent::class => [
                ['createTests', 30],
                ['dispatchNextCompileSourceMessage', 20],
                ['dispatchNextTestExecuteMessage', 0],
            ],
        ];
    }

    public function createTests(SourceCompileSuccessEvent $event): void
    {
        $suiteManifest = $event->getOutput();

        $this->testFactory->createFromManifestCollection($suiteManifest->getTestManifests());
    }

    public function dispatchNextCompileSourceMessage(): void
    {
        $this->compilationWorkflowHandler->dispatchNextCompileSourceMessage();
    }

    public function dispatchNextTestExecuteMessage(): void
    {
        if ($this->compilationWorkflowHandler->isComplete() && $this->executionWorkflowHandler->isReadyToExecute()) {
            $this->executionWorkflowHandler->dispatchNextExecuteTestMessage();
        }
    }
}
