<?php

declare(strict_types=1);

namespace App\Tests\Model;

use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\Event;

class ExpectedDispatchedEvent
{
    /**
     * @var callable
     */
    private $assertions;

    public function __construct(callable $assertions)
    {
        $this->assertions = $assertions;
    }

    public static function createAssertEquals(Event $expectedEvent): self
    {
        return new ExpectedDispatchedEvent(
            function (Event $actualEvent) use ($expectedEvent) {
                TestCase::assertEquals($expectedEvent, $actualEvent);

                return true;
            }
        );
    }

    public function matches(Event $actual): bool
    {
        return ($this->assertions)($actual);
    }
}
