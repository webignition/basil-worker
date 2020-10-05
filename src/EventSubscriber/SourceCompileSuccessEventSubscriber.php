<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\SourceCompileSuccessEvent;
use App\Services\CompilationWorkflowHandler;
use App\Services\ManifestStore;
use App\Services\TestStore;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SourceCompileSuccessEventSubscriber implements EventSubscriberInterface
{
    private TestStore $testStore;
    private CompilationWorkflowHandler $compilationWorkflowHandler;
    private ManifestStore $manifestStore;

    public function __construct(
        TestStore $testStore,
        CompilationWorkflowHandler $compilationWorkflowHandler,
        ManifestStore $manifestStore
    ) {
        $this->testStore = $testStore;
        $this->compilationWorkflowHandler = $compilationWorkflowHandler;
        $this->manifestStore = $manifestStore;
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
        $suiteManifest = $event->getSuiteManifest();

        foreach ($suiteManifest->getTestManifests() as $testManifest) {
            $manifestPath = $this->manifestStore->store($testManifest);
            $this->testStore->createFromTestManifest($testManifest, $manifestPath);
        }
    }

    public function dispatchNextCompileSourceMessage(): void
    {
        $this->compilationWorkflowHandler->dispatchNextCompileSourceMessage();
    }
}
