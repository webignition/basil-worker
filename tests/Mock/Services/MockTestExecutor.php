<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services;

use App\Entity\Test;
use App\Services\TestExecutor;
use Mockery\MockInterface;

class MockTestExecutor
{
    /**
     * @var TestExecutor|MockInterface
     */
    private TestExecutor $testExecutor;

    public function __construct()
    {
        $this->testExecutor = \Mockery::mock(TestExecutor::class);
    }

    public function getMock(): TestExecutor
    {
        return $this->testExecutor;
    }

    public function withoutExecuteCall(): self
    {
        $this->testExecutor
            ->shouldNotReceive('execute');

        return $this;
    }

    public function withExecuteCall(Test $test): self
    {
        $this->testExecutor
            ->shouldReceive('execute')
            ->with($test);

        return $this;
    }
}
