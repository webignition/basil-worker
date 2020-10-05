<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services;

use App\Services\ManifestPathGenerator;
use Mockery\MockInterface;
use webignition\BasilCompilerModels\TestManifest;

class MockManifestPathGenerator
{
    /**
     * @var ManifestPathGenerator|MockInterface
     */
    private ManifestPathGenerator $generator;

    public function __construct()
    {
        $this->generator = \Mockery::mock(ManifestPathGenerator::class);
    }

    public function getMock(): ManifestPathGenerator
    {
        return $this->generator;
    }

    public function withGenerateCall(TestManifest $testManifest, string $path): self
    {
        $this->generator
            ->shouldReceive('generate')
            ->with($testManifest)
            ->andReturn($path);

        return $this;
    }
}
