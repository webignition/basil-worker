<?php

declare(strict_types=1);

namespace App\Tests\Services\InvokableFactory;

use App\Entity\Test;
use App\Entity\TestConfiguration;

class TestSetup
{
    private TestConfiguration $configuration;
    private string $source;
    private string $target;
    private int $stepCount;

    /**
     * @var Test::STATE_*
     */
    private string $state;

    public function __construct()
    {
        $this->configuration = TestConfiguration::create('chrome', 'http://example.com');
        $this->source = '/app/source/Test/test.yml';
        $this->target = '/app/tests/GeneratedTest.php';
        $this->stepCount = 1;
        $this->state = Test::STATE_AWAITING;
    }

    public function getConfiguration(): TestConfiguration
    {
        return $this->configuration;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function getStepCount(): int
    {
        return $this->stepCount;
    }

    /**
     * @return Test::STATE_*
     */
    public function getState(): string
    {
        return $this->state;
    }

    public function withSource(string $source): self
    {
        $new = clone $this;
        $new->source = $source;

        return $new;
    }

    public function withTarget(string $target): self
    {
        $new = clone $this;
        $new->target = $target;

        return $new;
    }

    /**
     * @param Test::STATE_* $state
     *
     * @return $this
     */
    public function withState(string $state): self
    {
        $new = clone $this;
        $new->state = $state;

        return $new;
    }

    public function withStepCount(int $stepCount): self
    {
        $new = clone $this;
        $new->stepCount = $stepCount;

        return $new;
    }
}
