<?php

declare(strict_types=1);

namespace App\Tests\Services;

use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;

class GuzzleClientFactory
{
    private GuzzleHttpClient $guzzleHttpClient;

    public function __construct(MockHandler $mockHandler)
    {
        $handlerStack = HandlerStack::create($mockHandler);

        $this->guzzleHttpClient = new GuzzleHttpClient([
            'handler' => $handlerStack,
        ]);
    }

    public function get(): GuzzleHttpClient
    {
        return $this->guzzleHttpClient;
    }
}
