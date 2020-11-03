<?php

declare(strict_types=1);

namespace App\Tests\Services\Integration;

use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\HandlerStack;

class GuzzleClientFactory
{
    private GuzzleHttpClient $guzzleHttpClient;

    public function __construct(HandlerStack $handlerStack)
    {
        $this->guzzleHttpClient = new GuzzleHttpClient([
            'handler' => $handlerStack,
        ]);
    }

    public function get(): GuzzleHttpClient
    {
        return $this->guzzleHttpClient;
    }
}
