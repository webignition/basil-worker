<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\SourcesAddedEvent;
use App\Services\CompilationWorkflowHandler;
use App\Services\JobStateMutator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SourcesAddedEventSubscriber implements EventSubscriberInterface
{
    private JobStateMutator $jobStateMutator;
    private CompilationWorkflowHandler $compilationWorkflowHandler;

    public function __construct(
        JobStateMutator $jobStateMutator,
        CompilationWorkflowHandler $compilationWorkflowHandler
    ) {
        $this->jobStateMutator = $jobStateMutator;
        $this->compilationWorkflowHandler = $compilationWorkflowHandler;
    }

    public static function getSubscribedEvents()
    {
        return [
            SourcesAddedEvent::class => [
                ['setJobState', 10],
                ['dispatchNextCompileSourceMessage', 0],
            ],
        ];
    }

    public function setJobState(): void
    {
        $this->jobStateMutator->setCompilationRunning();
    }

    public function dispatchNextCompileSourceMessage(): void
    {
        $this->compilationWorkflowHandler->dispatchNextCompileSourceMessage();
    }
}
