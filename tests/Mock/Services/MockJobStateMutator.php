<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services;

use App\Services\JobStateMutator;
use Mockery\MockInterface;

class MockJobStateMutator
{
    /**
     * @var JobStateMutator|MockInterface
     */
    private JobStateMutator $jobStateMutator;

    public function __construct()
    {
        $this->jobStateMutator = \Mockery::mock(JobStateMutator::class);
    }

    public function getMock(): JobStateMutator
    {
        return $this->jobStateMutator;
    }
}
