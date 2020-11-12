<?php

declare(strict_types=1);

namespace App\Tests\Services\Guzzle;

use App\Tests\Services\Guzzle\Middleware\Factory\MiddlewareFactoryInterface;
use GuzzleHttp\HandlerStack;

class HandlerStackFactory
{
    /**
     * @param callable $handler
     * @param MiddlewareFactoryInterface[] $middlewareFactories
     *
     * @return HandlerStack
     */
    public function create(callable $handler, array $middlewareFactories = []): HandlerStack
    {
        $handlerStack = HandlerStack::create($handler);

        foreach ($middlewareFactories as $middlewareFactory) {
            if ($middlewareFactory instanceof MiddlewareFactoryInterface) {
                $arguments = $middlewareFactory->create();
                $handlerStack->push(
                    $arguments->getMiddleware(),
                    $arguments->getName()
                );
            }
        }

        return $handlerStack;
    }
}
