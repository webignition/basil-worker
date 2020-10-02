<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use Mockery\MockInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\EventDispatcher\Event;

class MockEventDispatcher
{
    /**
     * @var EventDispatcherInterface|MockInterface
     */
    private EventDispatcherInterface $eventDispatcher;

    public function __construct()
    {
        $this->eventDispatcher = \Mockery::mock(EventDispatcherInterface::class);
    }

    public function getMock(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    public function withDispatchCall(Event $event, string $name): self
    {
        $this->eventDispatcher
            ->shouldReceive('dispatch')
            ->andReturn($event, $name);

        return $this;
    }

    public function withoutDispatchCall(): self
    {
        $this->eventDispatcher
            ->shouldNotReceive('dispatch');

        return $this;
    }
}
