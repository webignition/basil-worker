<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\CompileSource;
use App\Services\CompilationStateFactory;
use App\Services\Compiler;
use App\Services\JobStore;
use App\Services\SourceCompileEventDispatcher;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class CompileSourceHandler implements MessageHandlerInterface
{
    private Compiler $compiler;
    private JobStore $jobStore;
    private SourceCompileEventDispatcher $eventDispatcher;
    private CompilationStateFactory $compilationStateFactory;

    public function __construct(
        Compiler $compiler,
        JobStore $jobStore,
        SourceCompileEventDispatcher $eventDispatcher,
        CompilationStateFactory $compilationStateFactory
    ) {
        $this->compiler = $compiler;
        $this->jobStore = $jobStore;
        $this->eventDispatcher = $eventDispatcher;
        $this->compilationStateFactory = $compilationStateFactory;
    }

    public function __invoke(CompileSource $message): void
    {
        if (false === $this->jobStore->hasJob()) {
            return;
        }

        if (false === $this->compilationStateFactory->is(CompilationStateFactory::STATE_RUNNING)) {
            return;
        }

        $source = $message->getSource();
        $output = $this->compiler->compile($source);

        $this->eventDispatcher->dispatch($source, $output);
    }
}
