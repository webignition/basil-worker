<?php

declare(strict_types=1);

namespace App\Tests\Services\Guzzle;

use GuzzleHttp\Handler\MockHandler as QueuingMockHandler;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use TijmenWierenga\Guzzle\Mocking\MockHandler as ClosureMockHandler;

class HandlerFactory
{
    public function createAlwaysOkMockHandler(): ClosureMockHandler
    {
        $mockHandler = new ClosureMockHandler();

        $mockHandler
            ->when(fn (RequestInterface $request): bool => $request->getMethod() === 'POST')
            ->respondWith(new Response(200));

        return $mockHandler;
    }

    public function createQueuingMockHandler(): QueuingMockHandler
    {
        return new QueuingMockHandler();
    }
}
