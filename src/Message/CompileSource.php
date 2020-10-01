<?php

declare(strict_types=1);

namespace App\Message;

class CompileSource
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
