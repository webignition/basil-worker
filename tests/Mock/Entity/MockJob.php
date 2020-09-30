<?php

declare(strict_types=1);

namespace App\Tests\Mock\Entity;

use App\Entity\Job;
use Mockery\MockInterface;

class MockJob
{
    /**
     * @var Job|MockInterface
     */
    private Job $job;

    public function __construct()
    {
        $this->job = \Mockery::mock(Job::class);
    }

    public function getMock(): Job
    {
        return $this->job;
    }

    /**
     * @param string[] $sources
     *
     * @return $this
     */
    public function withGetSourcesCall(array $sources): self
    {
        $this->job
            ->shouldReceive('getSources')
            ->andReturn($sources);

        return $this;
    }
}
