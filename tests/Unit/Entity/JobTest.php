<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Job;
use PHPUnit\Framework\TestCase;

class JobTest extends TestCase
{
    public function testSetState()
    {
        $job = Job::create('label', 'http://example.com/callback');
        self::assertSame(Job::STATE_COMPILATION_AWAITING, $job->getState());

        $state = Job::STATE_COMPILATION_RUNNING;
        $job->setState($state);
        self::assertSame($state, $job->getState());
    }
}
