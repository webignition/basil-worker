<?php

declare(strict_types=1);

namespace App\Services;

use App\Event\SourceCompile\SourceCompileSuccessEvent;
use App\Event\SourcesAddedEvent;
use App\Message\CompileSource;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class CompilationWorkflowHandler implements EventSubscriberInterface
{
    private MessageBusInterface $messageBus;
    private JobSourceFinder $jobSourceFinder;

    public function __construct(MessageBusInterface $messageBus, JobSourceFinder $jobSourceFinder)
    {
        $this->messageBus = $messageBus;
        $this->jobSourceFinder = $jobSourceFinder;
    }

    public static function getSubscribedEvents()
    {
        return [
            SourceCompileSuccessEvent::class => [
                ['dispatchNextCompileSourceMessage', 50],
            ],
            SourcesAddedEvent::class => [
                ['dispatchNextCompileSourceMessage', 50],
            ],
        ];
    }

    public function dispatchNextCompileSourceMessage(): void
    {
        $nextNonCompiledSource = $this->jobSourceFinder->findNextNonCompiledSource();

        if (is_string($nextNonCompiledSource)) {
            $message = new CompileSource($nextNonCompiledSource);
            $this->messageBus->dispatch($message);
        }
    }
}
