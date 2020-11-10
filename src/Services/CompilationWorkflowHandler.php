<?php

declare(strict_types=1);

namespace App\Services;

use App\Event\SourceCompile\SourceCompileSuccessEvent;
use App\Event\SourcesAddedEvent;
use App\Message\CompileSource;
use App\Model\Workflow\CompilationWorkflow;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class CompilationWorkflowHandler implements EventSubscriberInterface
{
    private MessageBusInterface $messageBus;
    private CompilationWorkflowFactory $compilationWorkflowFactory;

    public function __construct(MessageBusInterface $messageBus, CompilationWorkflowFactory $compilationWorkflowFactory)
    {
        $this->messageBus = $messageBus;
        $this->compilationWorkflowFactory = $compilationWorkflowFactory;
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
        $workflow = $this->compilationWorkflowFactory->create();
        $nextNonCompiledSource = $workflow->getNextSource();

        if (is_string($nextNonCompiledSource)) {
            $message = new CompileSource($nextNonCompiledSource);
            $this->messageBus->dispatch($message);
        }
    }

    public function isComplete(): bool
    {
        return CompilationWorkflow::STATE_COMPLETE === $this->compilationWorkflowFactory->create()->getState();
    }
}
