<?php

declare(strict_types=1);

namespace App\Tests\Mock\Entity;

use App\Entity\Test;
use Mockery\MockInterface;

class MockTest
{
    /**
     * @var Test|MockInterface
     */
    private Test $test;

    public function __construct()
    {
        $this->test = \Mockery::mock(Test::class);
    }

    public function getMock(): Test
    {
        return $this->test;
    }

    public function withGetStateCall(string $state): self
    {
        $this->test
            ->shouldReceive('getState')
            ->andReturn($state);

        return $this;
    }
}
