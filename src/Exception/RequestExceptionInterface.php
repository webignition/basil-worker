<?php

declare(strict_types=1);

namespace App\Exception;

interface RequestExceptionInterface
{
    public function getType(): string;
    public function getResponseMessage(): string;
    public function getResponseCode(): int;
}
