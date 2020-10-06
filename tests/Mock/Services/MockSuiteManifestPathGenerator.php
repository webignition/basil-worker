<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services;

use App\Services\SuiteManifestPathGenerator;
use Mockery\MockInterface;
use webignition\BasilCompilerModels\SuiteManifest;

class MockSuiteManifestPathGenerator
{
    /**
     * @var SuiteManifestPathGenerator|MockInterface
     */
    private SuiteManifestPathGenerator $generator;

    public function __construct()
    {
        $this->generator = \Mockery::mock(SuiteManifestPathGenerator::class);
    }

    public function getMock(): SuiteManifestPathGenerator
    {
        return $this->generator;
    }

    public function withGenerateCall(SuiteManifest $suiteManifest, string $path): self
    {
        $this->generator
            ->shouldReceive('generate')
            ->with($suiteManifest)
            ->andReturn($path);

        return $this;
    }
}
