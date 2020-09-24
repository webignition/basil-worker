<?php

declare(strict_types=1);

namespace App\Request;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class JobCreateRequest
{
    private string $label;
    private string $callbackUrl;
    private UploadedFile $manifest;

    /**
     * @var UploadedFile[]
     */
    private array $sources;

    /**
     * @param string $label
     * @param string $callbackUrl
     * @param UploadedFile $manifest
     * @param UploadedFile[] $sources
     */
    public function __construct(string $label, string $callbackUrl, UploadedFile $manifest, array $sources)
    {
        $this->label = $label;
        $this->callbackUrl = $callbackUrl;
        $this->manifest = $manifest;
        $this->sources = $sources;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getCallbackUrl(): string
    {
        return $this->callbackUrl;
    }

    public function getManifest(): UploadedFile
    {
        return $this->manifest;
    }

    /**
     * @return UploadedFile[]
     */
    public function getSources(): array
    {
        return $this->sources;
    }
}
