<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services;

use App\Model\ExecutionState;
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

    public function withCreateCall(ExecutionState $executionState): self
    {
        $this->executionStateFactory
            ->shouldReceive('create')
            ->andReturn($executionState);

        return $this;
    }
}
