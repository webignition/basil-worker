<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\SourceCompileSuccessEvent;
use App\Services\CompilationWorkflowHandler;
use App\Services\TestStore;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SourceCompileSuccessEventSubscriber implements EventSubscriberInterface
{
    private TestStore $testStore;
    private CompilationWorkflowHandler $compilationWorkflowHandler;

    public function __construct(TestStore $testStore, CompilationWorkflowHandler $compilationWorkflowHandler)
    {
        $this->testStore = $testStore;
        $this->compilationWorkflowHandler = $compilationWorkflowHandler;
    }

    public static function getSubscribedEvents()
    {
        return [
            SourceCompileSuccessEvent::NAME => [
                ['createTests', 10],
                ['dispatchNextCompileSourceMessage', 0],
            ],
        ];
    }

    public function createTests(SourceCompileSuccessEvent $event): void
    {
        $this->testStore->createFromTestManifests($event->getTestManifests());
    }

    public function dispatchNextCompileSourceMessage(): void
    {
        $this->compilationWorkflowHandler->dispatchNextCompileSourceMessage();
    }
}
