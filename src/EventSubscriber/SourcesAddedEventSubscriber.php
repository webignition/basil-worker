<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\SourcesAddedEvent;
use App\Services\CompilationWorkflowHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SourcesAddedEventSubscriber implements EventSubscriberInterface
{
    private CompilationWorkflowHandler $compilationWorkflowHandler;

    public function __construct(CompilationWorkflowHandler $compilationWorkflowHandler)
    {
        $this->compilationWorkflowHandler = $compilationWorkflowHandler;
    }

    public static function getSubscribedEvents()
    {
        return [
            SourcesAddedEvent::class => [
                ['dispatchNextCompileSourceMessage', 0],
            ],
        ];
    }

    public function dispatchNextCompileSourceMessage(): void
    {
        $this->compilationWorkflowHandler->dispatchNextCompileSourceMessage();
    }
}
