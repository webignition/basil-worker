<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services;

use App\Entity\Test;
use App\Services\TestStore;
use Mockery\MockInterface;

class MockTestStore
{
    /**
     * @var TestStore|MockInterface
     */
    private TestStore $testStore;

    public function __construct()
    {
        $this->testStore = \Mockery::mock(TestStore::class);
    }

    public function getMock(): TestStore
    {
        return $this->testStore;
    }

    public function withoutFindCall(): self
    {
        $this->testStore
            ->shouldNotReceive('find');

        return $this;
    }

    public function withFindCall(int $testId, ?Test $test): self
    {
        $this->testStore
            ->shouldReceive('find')
            ->with($testId)
            ->andReturn($test);

        return $this;
    }
}
