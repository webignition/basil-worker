<?php

declare(strict_types=1);

namespace App\Services;

use App\Message\CompileSource;
use Symfony\Component\Messenger\MessageBusInterface;

class CompilationWorkflowHandler
{
    private MessageBusInterface $messageBus;
    private JobSourceFinder $jobSourceFinder;

    public function __construct(MessageBusInterface $messageBus, JobSourceFinder $jobSourceFinder)
    {
        $this->messageBus = $messageBus;
        $this->jobSourceFinder = $jobSourceFinder;
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
