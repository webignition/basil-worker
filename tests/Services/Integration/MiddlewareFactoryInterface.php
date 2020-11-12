<?php

declare(strict_types=1);

namespace App\Tests\Services\Integration;

interface MiddlewareFactoryInterface
{
    public function create(): MiddlewareArguments;
}
