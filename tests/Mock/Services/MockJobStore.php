<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services;

use Mockery\MockInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Job;
use webignition\BasilWorker\PersistenceBundle\Services\Store\JobStore;

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

    public function withHasCall(bool $return): self
    {
        $this->jobStore
            ->shouldReceive('has')
            ->andReturn($return);

        return $this;
    }

    public function withGetCall(Job $job): self
    {
        $this->jobStore
            ->shouldReceive('get')
            ->andReturn($job);

        return $this;
    }
}
