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

    public function withGetStateCall(string $state): self
    {
        $this->job
            ->shouldReceive('getState')
            ->andReturn($state);

        return $this;
    }

    public function withIsRunningCall(bool $isRunning): self
    {
        $this->job
            ->shouldReceive('isRunning')
            ->andReturn($isRunning);

        return $this;
    }
}
