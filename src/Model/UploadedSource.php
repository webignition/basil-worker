<?php

declare(strict_types=1);

namespace App\Model;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadedSource
{
    private string $path;
    private UploadedFile $uploadedFile;

    public function __construct(string $path, UploadedFile $uploadedFile)
    {
        $this->path = $path;
        $this->uploadedFile = $uploadedFile;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getUploadedFile(): UploadedFile
    {
        return $this->uploadedFile;
    }
}
