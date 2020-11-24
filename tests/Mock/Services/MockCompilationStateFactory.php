<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services;

use App\Model\CompilationState;
use App\Services\CompilationStateFactory;
use Mockery\MockInterface;

class MockCompilationStateFactory
{
    /**
     * @var CompilationStateFactory|MockInterface
     */
    private CompilationStateFactory $compilationStateFactory;

    public function __construct()
    {
        $this->compilationStateFactory = \Mockery::mock(CompilationStateFactory::class);
    }

    public function getMock(): CompilationStateFactory
    {
        return $this->compilationStateFactory;
    }

    public function withCreateCall(CompilationState $compilationState): self
    {
        $this->compilationStateFactory
            ->shouldReceive('create')
            ->andReturn($compilationState);

        return $this;
    }
}
