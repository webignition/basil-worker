<?php

declare(strict_types=1);

namespace App\Tests\Services\Guzzle\Middleware\Factory;

use App\Tests\Services\Integration\MiddlewareArguments;

interface MiddlewareFactoryInterface
{
    public function create(): MiddlewareArguments;
}
