<?php

declare(strict_types=1);

namespace App\Tests\Mock\Model;

use App\Model\Manifest;
use Mockery\MockInterface;

class MockManifest
{
    /**
     * @var Manifest|MockInterface
     */
    private Manifest $manifest;

    public function __construct()
    {
        $this->manifest = \Mockery::mock(Manifest::class);
    }

    public function getMock(): Manifest
    {
        return $this->manifest;
    }

    /**
     * @param string[] $testPaths
     *
     * @return $this
     */
    public function withGetTestPathsCall(array $testPaths): self
    {
        $this->manifest
            ->shouldReceive('getTestPaths')
            ->andReturn($testPaths);

        return $this;
    }
}
