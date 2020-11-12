<?php

declare(strict_types=1);

namespace App\Tests\Services\Guzzle\Middleware\Factory;

use App\Tests\Services\Guzzle\Middleware\Factory\MiddlewareFactoryInterface;
use App\Tests\Services\Guzzle\Middleware\MiddlewareArguments;
use GuzzleHttp\Middleware;
use webignition\HttpHistoryContainer\LoggableContainer;

class HistoryMiddlewareFactory implements MiddlewareFactoryInterface
{
    private LoggableContainer $container;

    public function __construct(LoggableContainer $container)
    {
        $this->container = $container;
    }

    public function create(): MiddlewareArguments
    {
        return new MiddlewareArguments(
            Middleware::history($this->container),
            'history'
        );
    }
}
