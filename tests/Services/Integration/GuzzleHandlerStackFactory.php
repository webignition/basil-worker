<?php

declare(strict_types=1);

namespace App\Tests\Services\Integration;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use webignition\HttpHistoryContainer\LoggableContainer;

class GuzzleHandlerStackFactory
{
    public function create(callable $handler, LoggableContainer $historyContainer): HandlerStack
    {
        $handlerStack = HandlerStack::create($handler);
        $handlerStack->push(
            Middleware::history($historyContainer),
            'history'
        );

        return $handlerStack;
    }
}
