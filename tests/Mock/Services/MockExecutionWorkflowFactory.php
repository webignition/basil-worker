<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services;

use App\Model\Workflow\ExecutionWorkflow;
use App\Services\ExecutionWorkflowFactory;
use Mockery\MockInterface;

class MockExecutionWorkflowFactory
{
    /**
     * @var ExecutionWorkflowFactory|MockInterface
     */
    private ExecutionWorkflowFactory $executionWorkflowFactory;

    public function __construct()
    {
        $this->executionWorkflowFactory = \Mockery::mock(ExecutionWorkflowFactory::class);
    }

    public function getMock(): ExecutionWorkflowFactory
    {
        return $this->executionWorkflowFactory;
    }

    public function withCreateCall(ExecutionWorkflow $executionWorkflow): self
    {
        $this->executionWorkflowFactory
            ->shouldReceive('create')
            ->andReturn($executionWorkflow);

        return $this;
    }
}
