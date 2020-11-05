<?php

declare(strict_types=1);

namespace App\Tests\Model;

use Symfony\Contracts\EventDispatcher\Event;

class ExpectedDispatchedEvent
{
    private Event $event;

    public function __construct(Event $event)
    {
        $this->event = $event;
    }

    public function getEvent(): Event
    {
        return $this->event;
    }
}
