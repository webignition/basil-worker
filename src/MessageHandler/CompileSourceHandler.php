<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Job;
use App\Message\CompileSource;
use App\Services\Compiler;
use App\Services\JobStore;
use App\Services\SourceCompileEventDispatcher;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class CompileSourceHandler implements MessageHandlerInterface
{
    private Compiler $compiler;
    private JobStore $jobStore;
    private SourceCompileEventDispatcher $eventDispatcher;

    public function __construct(Compiler $compiler, JobStore $jobStore, SourceCompileEventDispatcher $eventDispatcher)
    {
        $this->compiler = $compiler;
        $this->jobStore = $jobStore;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function __invoke(CompileSource $message): void
    {
        if (false === $this->jobStore->hasJob()) {
            return;
        }

        $job = $this->jobStore->getJob();
        if (Job::STATE_COMPILATION_RUNNING !== $job->getState()) {
            return;
        }

        $source = $message->getSource();
        $output = $this->compiler->compile($source);

        $this->eventDispatcher->dispatch($source, $output);
    }
}
