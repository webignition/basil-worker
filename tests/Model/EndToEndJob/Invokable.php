<?php

declare(strict_types=1);

namespace App\Tests\Model\EndToEndJob;

class Invokable implements InvokableInterface, InvokableItemInterface
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

    /**
     * @return ServiceReference[]
     */
    public function getServiceReferences(): array
    {
        $serviceReferences = [];
        foreach ($this->arguments as $argument) {
            if ($argument instanceof ServiceReference) {
                $serviceReferences[$argument->getId()] = $argument;
            }
        }

        return $serviceReferences;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function replaceServiceReference(ServiceReference $serviceReference, object $service): void
    {
        foreach ($this->arguments as $argumentIndex => $argument) {
            if ($argument instanceof ServiceReference && $argument->getId() === $serviceReference->getId()) {
                $this->arguments[$argumentIndex] = $service;
            }
        }
    }
}
