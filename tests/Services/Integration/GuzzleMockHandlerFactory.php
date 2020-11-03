<?php

declare(strict_types=1);

namespace App\Tests\Services\Integration;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use TijmenWierenga\Guzzle\Mocking\MockHandler;

class GuzzleMockHandlerFactory
{
    public function create(): MockHandler
    {
        $mockHandler = new MockHandler();

        $mockHandler
            ->when(fn (RequestInterface $request): bool => $request->getMethod() === 'POST')
            ->respondWith(new Response(200));

        return $mockHandler;
    }
}
