<?php

declare(strict_types=1);

namespace App\Tests\Model;

use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Job;
use webignition\BasilWorker\PersistenceBundle\Entity\Source;
use webignition\BasilWorker\PersistenceBundle\Entity\Test;

class Environment
{
    private ?Job $job;

    /**
     * @var Source[]
     */
    private array $sources = [];

    /**
     * @var Test[]
     */
    private array $tests = [];

    /**
     * @var CallbackInterface[]
     */
    private array $callbacks = [];

    public function getJob(): ?Job
    {
        return $this->job;
    }

    /**
     * @return Source[]
     */
    public function getSources(): array
    {
        return $this->sources;
    }

    /**
     * @return Test[]
     */
    public function getTests(): array
    {
        return $this->tests;
    }

    /**
     * @return CallbackInterface[]
     */
    public function getCallbacks(): array
    {
        return $this->callbacks;
    }

    public function withJob(Job $job): self
    {
        $new = clone $this;
        $new->job = $job;

        return $new;
    }

    /**
     * @param Source[] $sources
     */
    public function withSources(array $sources): self
    {
        $new = clone $this;
        $new->sources = $sources;

        return $new;
    }

    /**
     * @param Test[] $tests
     */
    public function withTests(array $tests): self
    {
        $new = clone $this;
        $new->tests = $tests;

        return $new;
    }

    /**
     * @param CallbackInterface[] $callbacks
     */
    public function withCallbacks(array $callbacks): self
    {
        $new = clone $this;
        $new->callbacks = $callbacks;

        return $new;
    }
}
