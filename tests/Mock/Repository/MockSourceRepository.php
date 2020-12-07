<?php

declare(strict_types=1);

namespace App\Tests\Mock\Repository;

use App\Entity\Source;
use App\Repository\SourceRepository;
use Mockery\MockInterface;

class MockSourceRepository
{
    /**
     * @var SourceRepository|MockInterface
     */
    private SourceRepository $sourceRepository;

    public function __construct()
    {
        $this->sourceRepository = \Mockery::mock(SourceRepository::class);
    }

    public function getMock(): SourceRepository
    {
        return $this->sourceRepository;
    }

    /**
     * @param Source[] $sources
     *
     * @return $this
     */
    public function withFindAllCall(array $sources): self
    {
        $this->sourceRepository
            ->shouldReceive('findAll')
            ->withNoArgs()
            ->andReturn($sources);

        return $this;
    }
}
