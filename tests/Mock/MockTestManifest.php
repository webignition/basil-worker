<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use Mockery\MockInterface;
use webignition\BasilCompilerModels\TestManifest;

class MockTestManifest
{
    /**
     * @var TestManifest|MockInterface
     */
    private TestManifest $testManifest;

    public function __construct()
    {
        $this->testManifest = \Mockery::mock(TestManifest::class);
    }

    public function getMock(): TestManifest
    {
        return $this->testManifest;
    }

    /**
     * @param array<mixed> $data
     *
     * @return $this
     */
    public function withGetDataCall(array $data): self
    {
        $this->testManifest
            ->shouldReceive('getData')
            ->andReturn($data);

        return $this;
    }
}
