<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\SourceCompile\SourceCompileSuccessEvent;
use App\Services\CompilationWorkflowHandler;
use App\Services\ExecutionWorkflowHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SourceCompileSuccessEventSubscriber implements EventSubscriberInterface
{
    private CompilationWorkflowHandler $compilationWorkflowHandler;
    private ExecutionWorkflowHandler $executionWorkflowHandler;

    public function __construct(
        CompilationWorkflowHandler $compilationWorkflowHandler,
        ExecutionWorkflowHandler $executionWorkflowHandler
    ) {
        $this->compilationWorkflowHandler = $compilationWorkflowHandler;
        $this->executionWorkflowHandler = $executionWorkflowHandler;
    }

    public static function getSubscribedEvents()
    {
        return [
            SourceCompileSuccessEvent::class => [
                ['dispatchNextTestExecuteMessage', 0],
            ],
        ];
    }

    public function dispatchNextTestExecuteMessage(): void
    {
        if ($this->compilationWorkflowHandler->isComplete() && $this->executionWorkflowHandler->isReadyToExecute()) {
            $this->executionWorkflowHandler->dispatchNextExecuteTestMessage();
        }
    }
}
