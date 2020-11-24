<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\CompileSource;
use App\Services\CompilationState;
use App\Services\Compiler;
use App\Services\JobStore;
use App\Services\SourceCompileEventDispatcher;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class CompileSourceHandler implements MessageHandlerInterface
{
    private Compiler $compiler;
    private JobStore $jobStore;
    private SourceCompileEventDispatcher $eventDispatcher;
    private CompilationState $compilationState;

    public function __construct(
        Compiler $compiler,
        JobStore $jobStore,
        SourceCompileEventDispatcher $eventDispatcher,
        CompilationState $compilationState
    ) {
        $this->compiler = $compiler;
        $this->jobStore = $jobStore;
        $this->eventDispatcher = $eventDispatcher;
        $this->compilationState = $compilationState;
    }

    public function __invoke(CompileSource $message): void
    {
        if (false === $this->jobStore->hasJob()) {
            return;
        }

        if (false === $this->compilationState->is(CompilationState::STATE_RUNNING)) {
            return;
        }

        $source = $message->getSource();
        $output = $this->compiler->compile($source);

        $this->eventDispatcher->dispatch($source, $output);
    }
}
