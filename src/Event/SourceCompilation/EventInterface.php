<?php

declare(strict_types=1);

namespace App\Event\SourceCompilation;

use Psr\EventDispatcher\StoppableEventInterface;

interface EventInterface extends StoppableEventInterface
{
    public function getSource(): string;
}
