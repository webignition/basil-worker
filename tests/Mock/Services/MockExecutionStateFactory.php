<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services;

use App\Services\ExecutionStateFactory;
use Mockery\MockInterface;

class MockExecutionStateFactory
{
    /**
     * @var ExecutionStateFactory|MockInterface
     */
    private ExecutionStateFactory $executionStateFactory;

    public function __construct()
    {
        $this->executionStateFactory = \Mockery::mock(ExecutionStateFactory::class);
    }

    public function getMock(): ExecutionStateFactory
    {
        return $this->executionStateFactory;
    }

    /**
     * @param array<ExecutionStateFactory::STATE_*> $states
     * @param bool $is
     *
     * @return $this
     */
    public function withIsCall(array $states, bool $is): self
    {
        $this->executionStateFactory
            ->shouldReceive('is')
            ->with(...$states)
            ->andReturn($is);

        return $this;
    }
}
