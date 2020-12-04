<?php

declare(strict_types=1);

namespace App\Exception;

abstract class AbstractTestSourceException extends \Exception implements TestSourceExceptionInterface
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
