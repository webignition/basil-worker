<?php

declare(strict_types=1);

namespace App\Tests\Mock\Model;

use App\Model\Workflow\ExecutionWorkflow;
use App\Model\Workflow\WorkflowInterface;
use Mockery\MockInterface;

class MockExecutionWorkflow
{
    /**
     * @var ExecutionWorkflow|MockInterface
     */
    private ExecutionWorkflow $executionWorkflow;

    public function __construct()
    {
        $this->executionWorkflow = \Mockery::mock(ExecutionWorkflow::class);
    }

    public function getMock(): ExecutionWorkflow
    {
        return $this->executionWorkflow;
    }

    /**
     * @param WorkflowInterface::STATE_* $state
     *
     * @return $this
     */
    public function withGetStateCall(string $state): self
    {
        $this->executionWorkflow
            ->shouldReceive('getState')
            ->andReturn($state);

        return $this;
    }
}
