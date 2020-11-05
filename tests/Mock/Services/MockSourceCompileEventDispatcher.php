<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services;

use App\Services\SourceCompileEventDispatcher;
use Mockery\MockInterface;
use webignition\BasilCompilerModels\OutputInterface;

class MockSourceCompileEventDispatcher
{
    /**
     * @var SourceCompileEventDispatcher|MockInterface
     */
    private SourceCompileEventDispatcher $eventDispatcher;

    public function __construct()
    {
        $this->eventDispatcher = \Mockery::mock(SourceCompileEventDispatcher::class);
    }

    public function getMock(): SourceCompileEventDispatcher
    {
        return $this->eventDispatcher;
    }

    public function withDispatchCall(string $source, OutputInterface $output): self
    {
        $this->eventDispatcher
            ->shouldReceive('dispatch')
            ->once()
            ->with($source, $output);

        return $this;
    }

    public function withoutDispatchCall(): self
    {
        $this->eventDispatcher
            ->shouldNotReceive('dispatch');

        return $this;
    }
}
