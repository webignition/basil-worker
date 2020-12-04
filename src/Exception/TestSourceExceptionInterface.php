<?php

declare(strict_types=1);

namespace App\Exception;

interface TestSourceExceptionInterface
{
    public function getPath(): string;
}
