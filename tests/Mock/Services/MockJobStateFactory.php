<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services;

use App\Model\JobState;
use App\Services\JobStateFactory;
use Mockery\MockInterface;

class MockJobStateFactory
{
    /**
     * @var JobStateFactory|MockInterface
     */
    private JobStateFactory $jobStateFactory;

    public function __construct()
    {
        $this->jobStateFactory = \Mockery::mock(JobStateFactory::class);
    }

    public function getMock(): JobStateFactory
    {
        return $this->jobStateFactory;
    }

    public function withCreateCall(JobState $jobState): self
    {
        $this->jobStateFactory
            ->shouldReceive('create')
            ->andReturn($jobState);

        return $this;
    }
}
