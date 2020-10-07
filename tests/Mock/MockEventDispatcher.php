<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
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
            ->withArgs(function (Event $passedEvent, string $passedName) use ($event, $name) {
                TestCase::assertEquals($event, $passedEvent);
                TestCase::assertSame($name, $passedName);

                return true;
            });

        return $this;
    }

    public function withoutDispatchCall(): self
    {
        $this->eventDispatcher
            ->shouldNotReceive('dispatch');

        return $this;
    }
}
