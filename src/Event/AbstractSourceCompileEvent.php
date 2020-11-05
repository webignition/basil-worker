<?php

declare(strict_types=1);

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

abstract class AbstractSourceCompileEvent extends Event implements SourceCompileEventInterface
{
    private string $source;

    public function __construct(string $source)
    {
        $this->source = $source;
    }

    public function getSource(): string
    {
        return $this->source;
    }
}
