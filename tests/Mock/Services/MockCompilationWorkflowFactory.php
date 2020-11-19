<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services;

use App\Model\Workflow\CompilationWorkflow;
use App\Services\CompilationWorkflowFactory;
use Mockery\MockInterface;

class MockCompilationWorkflowFactory
{
    /**
     * @var CompilationWorkflowFactory|MockInterface
     */
    private CompilationWorkflowFactory $compilationWorkflowFactory;

    public function __construct()
    {
        $this->compilationWorkflowFactory = \Mockery::mock(CompilationWorkflowFactory::class);
    }

    public function getMock(): CompilationWorkflowFactory
    {
        return $this->compilationWorkflowFactory;
    }

    public function withCreateCall(CompilationWorkflow $compilationWorkflow): self
    {
        $this->compilationWorkflowFactory
            ->shouldReceive('create')
            ->andReturn($compilationWorkflow);

        return $this;
    }
}
