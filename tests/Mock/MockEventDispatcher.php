<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use App\Tests\Model\ExpectedDispatchedEvent;
use App\Tests\Model\ExpectedDispatchedEventCollection;
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

    public function withDispatchCalls(ExpectedDispatchedEventCollection $expectedDispatchedEvents): self
    {
        $this->eventDispatcher
            ->shouldReceive('dispatch')
            ->withArgs(function (Event $passedEvent, string $passedName) use ($expectedDispatchedEvents) {
                static $dispatchCallIndex = 0;

                $expectedDispatchedEvent = $expectedDispatchedEvents[$dispatchCallIndex];

                if ($expectedDispatchedEvent instanceof ExpectedDispatchedEvent) {
                    TestCase::assertEquals($expectedDispatchedEvent->getEvent(), $passedEvent);
                    TestCase::assertSame($expectedDispatchedEvent->getName(), $passedName);
                }

                $dispatchCallIndex++;

                return $expectedDispatchedEvent instanceof ExpectedDispatchedEvent;
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
