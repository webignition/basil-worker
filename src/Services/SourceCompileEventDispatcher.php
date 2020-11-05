<?php

declare(strict_types=1);

namespace App\Services;

use App\Event\SourceCompileEventInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use webignition\BasilCompilerModels\OutputInterface;

class SourceCompileEventDispatcher
{
    private SourceCompileEventFactory $factory;
    private EventDispatcherInterface $dispatcher;

    public function __construct(SourceCompileEventFactory $factory, EventDispatcherInterface $dispatcher)
    {
        $this->factory = $factory;
        $this->dispatcher = $dispatcher;
    }

    public function dispatch(string $source, OutputInterface $output): void
    {
        $event = $this->factory->create($source, $output);
        if ($event instanceof SourceCompileEventInterface) {
            $this->dispatcher->dispatch($event);
        }
    }
}
