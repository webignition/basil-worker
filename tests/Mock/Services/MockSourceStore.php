<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services;

use Mockery\MockInterface;
use webignition\BasilWorker\PersistenceBundle\Services\Store\SourceStore;

class MockSourceStore
{
    /**
     * @var SourceStore|MockInterface
     */
    private SourceStore $sourceStore;

    public function __construct()
    {
        $this->sourceStore = \Mockery::mock(SourceStore::class);
    }

    public function getMock(): SourceStore
    {
        return $this->sourceStore;
    }

    public function withHasAnyCall(bool $hasAny): self
    {
        $this->sourceStore
            ->shouldReceive('hasAny')
            ->withNoArgs()
            ->andReturn($hasAny);

        return $this;
    }
}
