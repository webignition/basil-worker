<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services;

use App\Services\ExecutionWorkflowHandler;
use Mockery\MockInterface;

class MockExecutionWorkflowHandler
{
    /**
     * @var ExecutionWorkflowHandler|MockInterface
     */
    private ExecutionWorkflowHandler $executionWorkflowHandler;

    public function __construct()
    {
        $this->executionWorkflowHandler = \Mockery::mock(ExecutionWorkflowHandler::class);
    }

    public function getMock(): ExecutionWorkflowHandler
    {
        return $this->executionWorkflowHandler;
    }

    public function withIsReadyToExecuteCall(bool $isReadyToExecute): self
    {
        $this->executionWorkflowHandler
            ->shouldReceive('isReadyToExecute')
            ->andReturn($isReadyToExecute);

        return $this;
    }

    public function withIsCompleteCall(bool $isComplete): self
    {
        $this->executionWorkflowHandler
            ->shouldReceive('isComplete')
            ->andReturn($isComplete);

        return $this;
    }
}
