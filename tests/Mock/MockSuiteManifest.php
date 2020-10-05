<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use Mockery\MockInterface;
use webignition\BasilCompilerModels\SuiteManifest;
use webignition\BasilCompilerModels\TestManifest;

class MockSuiteManifest
{
    /**
     * @var SuiteManifest|MockInterface
     */
    private SuiteManifest $suiteManifest;

    public function __construct()
    {
        $this->suiteManifest = \Mockery::mock(SuiteManifest::class);
    }

    public function getMock(): SuiteManifest
    {
        return $this->suiteManifest;
    }

    /**
     * @param TestManifest[] $testManifests
     *
     * @return $this
     */
    public function withGetTestManifestsCall(array $testManifests): self
    {
        $this->suiteManifest
            ->shouldReceive('getTestManifests')
            ->andReturn($testManifests);

        return $this;
    }

    /**
     * @param array<mixed> $data
     *
     * @return $this
     */
    public function withGetDataCall(array $data): self
    {
        $this->suiteManifest
            ->shouldReceive('getData')
            ->andReturn($data);

        return $this;
    }
}
