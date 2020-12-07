<?php

declare(strict_types=1);

namespace App\Message;

class CompileSource
{
    private string $path;

    public function __construct(string $source)
    {
        $this->path = $source;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
