<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services;

use App\Services\CompilationWorkflowHandler;
use Mockery\MockInterface;

class MockCompilationWorkflowHandler
{
    /**
     * @var CompilationWorkflowHandler|MockInterface
     */
    private CompilationWorkflowHandler $compilationWorkflowHandler;

    public function __construct()
    {
        $this->compilationWorkflowHandler = \Mockery::mock(CompilationWorkflowHandler::class);
    }

    public function getMock(): CompilationWorkflowHandler
    {
        return $this->compilationWorkflowHandler;
    }

    public function withIsCompleteCall(bool $isComplete): self
    {
        $this->compilationWorkflowHandler
            ->shouldReceive('isComplete')
            ->andReturn($isComplete);

        return $this;
    }
}
