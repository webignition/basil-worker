<?php

declare(strict_types=1);

namespace App\Tests\Services\InvokableFactory;

class JobSetup
{
    private string $label;
    private string $callbackUrl;
    private int $maximumDurationInSeconds;
    private string $manifestPath;

    public function __construct()
    {
        $this->label = md5('label content');
        $this->callbackUrl = 'http://example.com/callback';
        $this->maximumDurationInSeconds = 600;
        $this->manifestPath = '';
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
