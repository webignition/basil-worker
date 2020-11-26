<?php

declare(strict_types=1);

namespace App\Tests\Services\InvokableFactory;

class JobSetup
{
    private string $label;
    private string $callbackUrl;
    private int $maximumDurationInSeconds;
    private string $manifestPath;

    /**
     * @var string[]
     */
    private ?array $sources;

    public function __construct()
    {
        $this->label = md5('label content');
        $this->callbackUrl = 'http://example.com/callback';
        $this->maximumDurationInSeconds = 600;
        $this->manifestPath = '';
        $this->sources = null;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getCallbackUrl(): string
    {
        return $this->callbackUrl;
    }

    public function getMaximumDurationInSeconds(): int
    {
        return $this->maximumDurationInSeconds;
    }

    public function getManifestPath(): string
    {
        return $this->manifestPath;
    }

    /**
     * @return string[]
     */
    public function getSources(): ?array
    {
        return $this->sources;
    }

    public function withLabel(string $label): self
    {
        $new = clone $this;
        $new->label = $label;

        return $new;
    }

    public function withCallbackUrl(string $callbackUrl): self
    {
        $new = clone $this;
        $new->callbackUrl = $callbackUrl;

        return $new;
    }

    /**
     * @param string[] $sources
     *
     * @return $this
     */
    public function withSources(array $sources): self
    {
        $new = clone $this;
        $new->sources = $sources;

        return $new;
    }

    public function withManifestPath(string $manifestPath): self
    {
        $new = clone $this;
        $new->manifestPath = $manifestPath;

        return $new;
    }

    public function withMaximumDurationInSeconds(int $maximumDurationInSeconds): self
    {
        $new = clone $this;
        $new->maximumDurationInSeconds = $maximumDurationInSeconds;

        return $new;
    }
}
