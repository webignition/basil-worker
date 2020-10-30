<?php

declare(strict_types=1);

namespace App\Services;

use App\Message\CompileSource;
use App\Model\Workflow\CompilationWorkflow;
use Symfony\Component\Messenger\MessageBusInterface;

class CompilationWorkflowHandler
{
    private MessageBusInterface $messageBus;
    private CompilationWorkflowFactory $compilationWorkflowFactory;

    public function __construct(MessageBusInterface $messageBus, CompilationWorkflowFactory $compilationWorkflowFactory)
    {
        $this->messageBus = $messageBus;
        $this->compilationWorkflowFactory = $compilationWorkflowFactory;
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
