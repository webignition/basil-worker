<?php

declare(strict_types=1);

namespace App\Tests\Model;

use Symfony\Contracts\EventDispatcher\Event;

class ExpectedDispatchedEvent
{
    private Event $event;
    private string $name;

    public function __construct(Event $event, string $name)
    {
        $this->event = $event;
        $this->name = $name;
    }

    public function getEvent(): Event
    {
        return $this->event;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
