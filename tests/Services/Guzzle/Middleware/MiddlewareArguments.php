<?php

declare(strict_types=1);

namespace App\Tests\Services\Guzzle\Middleware;

class MiddlewareArguments
{
    /**
     * @var callable
     */
    private $middleware;
    private string $name;

    public function __construct(callable $middleware, string $name)
    {
        $this->middleware = $middleware;
        $this->name = $name;
    }

    public function getMiddleware(): callable
    {
        return $this->middleware;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
