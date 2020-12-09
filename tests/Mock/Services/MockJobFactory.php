<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services;

use Mockery\MockInterface;
use webignition\BasilWorker\PersistenceBundle\Services\Factory\JobFactory;

class MockJobFactory
{
    /**
     * @var JobFactory|MockInterface
     */
    private JobFactory $jobFactory;

    public function __construct()
    {
        $this->jobFactory = \Mockery::mock(JobFactory::class);
    }

    public function getMock(): JobFactory
    {
        return $this->jobFactory;
    }

    public function withCreateCall(string $label, string $callbackUrl, int $maximumDurationInSeconds): self
    {
        $this->jobFactory
            ->shouldReceive('create')
            ->with($label, $callbackUrl, $maximumDurationInSeconds);

        return $this;
    }
}
