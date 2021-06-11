<?php

declare(strict_types=1);

namespace App\Tests\Model;

class JobSetup
{
    private string $label;
    private string $callbackUrl;
    private int $maximumDurationInSeconds;

    /**
     * @var string[]
     */
    private array $localSourcePaths;

    public function __construct()
    {
        $this->label = md5('label content');
        $this->callbackUrl = 'http://example.com/callback';
        $this->maximumDurationInSeconds = 600;
        $this->localSourcePaths = [];
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

    /**
     * @return string[]
     */
    public function getLocalSourcePaths(): array
    {
        return $this->localSourcePaths;
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
     * @param string[] $localSourcePaths
     */
    public function withLocalSourcePaths(array $localSourcePaths): self
    {
        $new = clone $this;
        $new->localSourcePaths = $localSourcePaths;

        return $new;
    }

    public function withMaximumDurationInSeconds(int $maximumDurationInSeconds): self
    {
        $new = clone $this;
        $new->maximumDurationInSeconds = $maximumDurationInSeconds;

        return $new;
    }
}
