<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\CompileSource;
use App\Model\JobState;
use App\Services\Compiler;
use App\Services\JobStateFactory;
use App\Services\JobStore;
use App\Services\SourceCompileEventDispatcher;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class CompileSourceHandler implements MessageHandlerInterface
{
    private Compiler $compiler;
    private JobStore $jobStore;
    private SourceCompileEventDispatcher $eventDispatcher;
    private JobStateFactory $jobStateFactory;

    public function __construct(
        Compiler $compiler,
        JobStore $jobStore,
        SourceCompileEventDispatcher $eventDispatcher,
        JobStateFactory $jobStateFactory
    ) {
        $this->compiler = $compiler;
        $this->jobStore = $jobStore;
        $this->eventDispatcher = $eventDispatcher;
        $this->jobStateFactory = $jobStateFactory;
    }

    public function __invoke(CompileSource $message): void
    {
        if (false === $this->jobStore->hasJob()) {
            return;
        }

        $jobState = $this->jobStateFactory->create();
        if (JobState::STATE_COMPILATION_RUNNING !== (string) $jobState) {
            return;
        }

        $source = $message->getSource();
        $output = $this->compiler->compile($source);

        $this->eventDispatcher->dispatch($source, $output);
    }
}
