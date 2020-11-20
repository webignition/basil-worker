<?php

declare(strict_types=1);

namespace App\Tests\Services\InvokableFactory;

use webignition\BasilModels\Test\Configuration;

class TestSetup
{
    private Configuration $configuration;
    private string $source;
    private string $target;
    private int $stepCount;

    public function __construct()
    {
        $this->configuration = new Configuration('chrome', 'http://example.com');
        $this->source = '/app/source/Test/test.yml';
        $this->target = '/app/tests/GeneratedTest.php';
        $this->stepCount = 1;
    }

    public function getConfiguration(): Configuration
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

    public function withSource(string $source): self
    {
        $new = clone $this;
        $new->source = $source;

        return $new;
    }
}
