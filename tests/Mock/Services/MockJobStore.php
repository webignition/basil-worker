<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services;

use App\Entity\Job;
use App\Services\JobStore;
use Mockery\MockInterface;

class MockJobStore
{
    /**
     * @var JobStore|MockInterface
     */
    private JobStore $jobStore;

    public function __construct()
    {
        $this->jobStore = \Mockery::mock(JobStore::class);
    }

    public function getMock(): JobStore
    {
        return $this->jobStore;
    }

    public function withHasJobCall(bool $return): self
    {
        $this->jobStore
            ->shouldReceive('hasJob')
            ->andReturn($return);

        return $this;
    }

    public function withCreateCall(string $label, string $callbackUrl): self
    {
        $this->jobStore
            ->shouldReceive('create')
            ->with($label, $callbackUrl);

        return $this;
    }

    public function withGetJobCall(Job $job): self
    {
        $this->jobStore
            ->shouldReceive('getJob')
            ->andReturn($job);

        return $this;
    }
}
