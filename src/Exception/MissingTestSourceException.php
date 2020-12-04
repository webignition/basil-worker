<?php

declare(strict_types=1);

namespace App\Exception;

class MissingTestSourceException extends \Exception
{
    private string $path;

    public function __construct(string $path)
    {
        parent::__construct();

        $this->path = $path;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
