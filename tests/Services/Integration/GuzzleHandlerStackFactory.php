<?php

declare(strict_types=1);

namespace App\Tests\Services\Integration;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use TijmenWierenga\Guzzle\Mocking\MockHandler;
use webignition\HttpHistoryContainer\LoggableContainer;

class GuzzleHandlerStackFactory
{
    public function create(MockHandler $handler, LoggableContainer $historyContainer): HandlerStack
    {
        $handlerStack = HandlerStack::create($handler);
        $handlerStack->push(
            Middleware::history($historyContainer),
            'history'
        );

        return $handlerStack;
    }
}
