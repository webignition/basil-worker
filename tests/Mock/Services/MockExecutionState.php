<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services;

use App\Services\ExecutionState;
use Mockery\MockInterface;

class MockExecutionState
{
    /**
     * @var ExecutionState|MockInterface
     */
    private ExecutionState $executionState;

    public function __construct()
    {
        $this->executionState = \Mockery::mock(ExecutionState::class);
    }

    public function getMock(): ExecutionState
    {
        return $this->executionState;
    }

    /**
     * @param array<ExecutionState::STATE_*> $states
     * @param bool $is
     *
     * @return $this
     */
    public function withIsCall(array $states, bool $is): self
    {
        $this->executionState
            ->shouldReceive('is')
            ->with(...$states)
            ->andReturn($is);

        return $this;
    }
}
