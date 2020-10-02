<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\SourcesAddedEvent;
use App\Message\CompileSource;
use App\Services\JobSourceFinder;
use App\Services\JobStateMutator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class SourcesAddedEventSubscriber implements EventSubscriberInterface
{
    private MessageBusInterface $messageBus;
    private JobSourceFinder $jobSourceFinder;
    private JobStateMutator $jobStateMutator;

    public function __construct(
        MessageBusInterface $messageBus,
        JobSourceFinder $jobSourceFinder,
        JobStateMutator $jobStateMutator
    ) {
        $this->messageBus = $messageBus;
        $this->jobSourceFinder = $jobSourceFinder;
        $this->jobStateMutator = $jobStateMutator;
    }

    public static function getSubscribedEvents()
    {
        return [
            SourcesAddedEvent::NAME => [
                ['setJobState', 10],
                ['dispatchCompileSourceMessage', 0],
            ],
        ];
    }

    public function setJobState(): void
    {
        $this->jobStateMutator->setCompilationRunning();
    }

    public function dispatchCompileSourceMessage(): void
    {
        $nextNonCompiledSource = $this->jobSourceFinder->findNextNonCompiledSource();

        if (is_string($nextNonCompiledSource)) {
            $message = new CompileSource($nextNonCompiledSource);
            $this->messageBus->dispatch($message);
        }
    }
}
