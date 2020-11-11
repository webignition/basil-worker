<?php

declare(strict_types=1);

namespace App\Tests\Model\EndToEndJob;

class Invokable
{
    /**
     * @var callable
     */
    private $callable;

    /**
     * @var array<mixed>
     */
    private array $arguments;

    /**
     * @param callable $callable
     * @param array<mixed> $arguments
     */
    public function __construct(callable $callable, array $arguments = [])
    {
        $this->callable = $callable;
        $this->arguments = $arguments;
    }

    public static function createEmpty(): Invokable
    {
        return new Invokable(
            function (): bool {
                return true;
            }
        );
    }

    /**
     * @param mixed ...$args
     *
     * @return mixed
     */
    public function __invoke(...$args)
    {
        return ($this->callable)(...$args, ...$this->arguments);
    }
}
