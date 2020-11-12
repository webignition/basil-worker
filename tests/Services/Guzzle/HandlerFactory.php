<?php

declare(strict_types=1);

namespace App\Tests\Services\Guzzle;

use GuzzleHttp\Handler\MockHandler as QueuingMockHandler;

class HandlerFactory
{
    public function createQueuingMockHandler(): QueuingMockHandler
    {
        return new QueuingMockHandler();
    }
}
