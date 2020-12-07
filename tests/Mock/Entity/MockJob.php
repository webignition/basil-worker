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

    public function withGetCallbackUrlCall(string $callbackUrl): self
    {
        $this->job
            ->shouldReceive('getCallbackUrl')
            ->andReturn($callbackUrl);

        return $this;
    }

    public function withGetLabelCall(string $label): self
    {
        $this->job
            ->shouldReceive('getLabel')
            ->andReturn($label);

        return $this;
    }

    public function withHasReachedMaximumDurationCall(bool $hasReachedMaximumDuration): self
    {
        $this->job
            ->shouldReceive('hasReachedMaximumDuration')
            ->andReturn($hasReachedMaximumDuration);

        return $this;
    }

    public function withGetMaximumDurationInSecondsCall(int $maximumDurationInSeconds): self
    {
        $this->job
            ->shouldReceive('getMaximumDurationInSeconds')
            ->andReturn($maximumDurationInSeconds);

        return $this;
    }
}
