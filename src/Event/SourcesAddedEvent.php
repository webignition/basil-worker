<?php

declare(strict_types=1);

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

class SourcesAddedEvent extends Event
{
    public const NAME = 'worker.sources.added';
}
