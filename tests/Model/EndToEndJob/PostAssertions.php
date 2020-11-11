<?php

declare(strict_types=1);

namespace App\Tests\Model\EndToEndJob;

class PostAssertions
{
    /**
     * @var callable
     */
    private $assertions;

    /**
     * @var array<mixed>
     */
    private array $arguments;

    public function __construct(callable $assertions, array $arguments = [])
    {
        $this->assertions = $assertions;
        $this->arguments = $arguments;
    }

    public function getAssertions(): callable
    {
        return $this->assertions;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function __invoke(): void
    {
        ($this->assertions)(...$this->arguments);
    }
}
