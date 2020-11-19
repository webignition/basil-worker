<?php

declare(strict_types=1);

namespace App\Tests\Mock\Model;

use App\Model\Workflow\CompilationWorkflow;
use App\Model\Workflow\WorkflowInterface;
use Mockery\MockInterface;

class MockCompilationWorkflow
{
    /**
     * @var CompilationWorkflow|MockInterface
     */
    private CompilationWorkflow $compilationWorkflow;

    public function __construct()
    {
        $this->compilationWorkflow = \Mockery::mock(CompilationWorkflow::class);
    }

    public function getMock(): CompilationWorkflow
    {
        return $this->compilationWorkflow;
    }

    /**
     * @param WorkflowInterface::STATE_* $state
     *
     * @return $this
     */
    public function withGetStateCall(string $state): self
    {
        $this->compilationWorkflow
            ->shouldReceive('getState')
            ->andReturn($state);

        return $this;
    }
}
