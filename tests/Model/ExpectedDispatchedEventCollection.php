<?php

declare(strict_types=1);

namespace App\Tests\Model;

/**
 * @implements \ArrayAccess<int, ExpectedDispatchedEvent>
 */
class ExpectedDispatchedEventCollection implements \ArrayAccess
{
    /**
     * @var ExpectedDispatchedEvent[]
     */
    private array $expectedDispatchedEvents;

    /**
     * @param array<mixed> $expectedDispatchedEvents
     */
    public function __construct(array $expectedDispatchedEvents)
    {
        $this->expectedDispatchedEvents = array_filter($expectedDispatchedEvents, function ($item): bool {
            return $item instanceof ExpectedDispatchedEvent;
        });
    }

    public function offsetExists($offset): bool
    {
        return null !== $this->offsetGet($offset);
    }

    public function offsetGet($offset): ?ExpectedDispatchedEvent
    {
        return $this->expectedDispatchedEvents[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        if (false === is_int($offset)) {
            return;
        }

        if (!$value instanceof ExpectedDispatchedEvent) {
            return;
        }

        $this->expectedDispatchedEvents[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->expectedDispatchedEvents[$offset]);
    }
}
