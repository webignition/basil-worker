<?php

declare(strict_types=1);

namespace App\Tests\Model\EndToEndJob;

class JobConfiguration
{
    private string $label;
    private string $callbackUrl;
    private string $manifestPath;

    public function __construct(string $label, string $callbackUrl, string $manifestPath)
    {
        $this->label = $label;
        $this->callbackUrl = $callbackUrl;
        $this->manifestPath = $manifestPath;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getCallbackUrl(): string
    {
        return $this->callbackUrl;
    }

    public function getManifestPath(): string
    {
        return $this->manifestPath;
    }
}
