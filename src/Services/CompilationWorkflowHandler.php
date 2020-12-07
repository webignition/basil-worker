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
    private SourcePathFinder $sourcePathFinder;

    public function __construct(MessageBusInterface $messageBus, SourcePathFinder $sourcePathFinder)
    {
        $this->messageBus = $messageBus;
        $this->sourcePathFinder = $sourcePathFinder;
    }

    /**
     * @return array[][]
     */
    public static function getSubscribedEvents(): array
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
        $nextNonCompiledSource = $this->sourcePathFinder->findNextNonCompiledPath();

        if (is_string($nextNonCompiledSource)) {
            $message = new CompileSource($nextNonCompiledSource);
            $this->messageBus->dispatch($message);
        }
    }
}
