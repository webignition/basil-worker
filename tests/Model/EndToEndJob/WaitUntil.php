<?php

declare(strict_types=1);

namespace App\Tests\Model\EndToEndJob;

use App\Entity\Job;

class WaitUntil
{
    /**
     * @var callable
     */
    private $callable;

    /**
     * @var array<mixed>
     */
    private array $arguments;

    public function __construct(callable $assertions, array $arguments = [])
    {
        $this->callable = $assertions;
        $this->arguments = $arguments;
    }

    public static function createSynchronous(): WaitUntil
    {
        return new WaitUntil(function () {
            return true;
        });
    }

    public function getCallable(): callable
    {
        return $this->callable;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function __invoke(Job $job): bool
    {
        return ($this->callable)($job, ...$this->arguments);
    }
}
