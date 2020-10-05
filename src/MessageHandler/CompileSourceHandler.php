<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Job;
use App\Event\SourceCompileFailureEvent;
use App\Event\SourceCompileSuccessEvent;
use App\Message\CompileSource;
use App\Services\Compiler;
use App\Services\JobStore;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use webignition\BasilCompilerModels\ErrorOutputInterface;
use webignition\BasilCompilerModels\SuiteManifest;

class CompileSourceHandler implements MessageHandlerInterface
{
    private Compiler $compiler;
    private JobStore $jobStore;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(Compiler $compiler, JobStore $jobStore, EventDispatcherInterface $eventDispatcher)
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

        if ($output instanceof ErrorOutputInterface) {
            $this->eventDispatcher->dispatch(
                new SourceCompileFailureEvent($source, $output),
                SourceCompileFailureEvent::NAME
            );
        }

        if ($output instanceof SuiteManifest) {
            $this->eventDispatcher->dispatch(
                new SourceCompileSuccessEvent($source, $output),
                SourceCompileSuccessEvent::NAME
            );
        }
    }
}
