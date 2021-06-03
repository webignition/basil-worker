<?php

declare(strict_types=1);

namespace App\Tests\Services;

use Psr\Container\ContainerInterface;

class CallableInvoker
{
    public function __construct(
        private ContainerInterface $container
    ) {
    }

    public function invoke(callable $callable): mixed
    {
        if ($callable instanceof \Closure) {
            $reflectionFunction = new \ReflectionFunction($callable);
            $args = [];

            foreach ($reflectionFunction->getParameters() as $parameter) {
                $type = $parameter->getType();
                if ($type instanceof \ReflectionNamedType) {
                    $class = $type->getName();
                    $service = $this->container->get($class);

                    if ($service instanceof $class) {
                        $args[] = $service;
                    }
                }
            }

            return $callable(...$args);
        }

        return null;
    }
}
