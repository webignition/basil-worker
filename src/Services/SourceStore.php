<?php

declare(strict_types=1);

namespace App\Services;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class SourceStore
{
    private string $path;

    public function __construct(string $path)
    {
        $this->path = (string) $path;
    }

    public function initialize(): bool
    {
        return true;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function store(UploadedFile $uploadedFile, string $relativePath): File
    {
        $directory = $this->path . '/' . dirname($relativePath);
        $filename = basename($relativePath);

        $path = $directory . '/' . $filename;
        if (file_exists($path)) {
            unlink($path);
        }

        return $uploadedFile->move($directory, $filename);
    }
}
