<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use Mockery\MockInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class MockArgumentMetadata
{
    /**
     * @var ArgumentMetadata|MockInterface
     */
    private ArgumentMetadata $argumentMetadata;

    public function __construct()
    {
        $this->argumentMetadata = \Mockery::mock(ArgumentMetadata::class);
    }

    public function getMock(): ArgumentMetadata
    {
        return $this->argumentMetadata;
    }

    public function withGetTypeCall(string $type): self
    {
        $this->argumentMetadata
            ->shouldReceive('getType')
            ->andReturn($type);

        return $this;
    }
}
