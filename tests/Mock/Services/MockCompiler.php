<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services;

use App\Services\Compiler;
use Mockery\MockInterface;
use webignition\BasilCompilerModels\OutputInterface;

class MockCompiler
{
    /**
     * @var Compiler|MockInterface
     */
    private Compiler $compiler;

    public function __construct()
    {
        $this->compiler = \Mockery::mock(Compiler::class);
    }

    public function getMock(): Compiler
    {
        return $this->compiler;
    }

    public function withCompileCall(string $source, OutputInterface $output): self
    {
        $this->compiler
            ->shouldReceive('compile')
            ->with($source)
            ->andReturn($output);

        return $this;
    }
}
