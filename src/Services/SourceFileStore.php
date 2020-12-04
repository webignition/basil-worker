<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\UploadedSource;
use Symfony\Component\HttpFoundation\File\File;

class SourceFileStore
{
    private string $path;

    public function __construct(string $path)
    {
        $this->path = (string) $path;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function store(UploadedSource $uploadedSource, string $relativePath): File
    {
        $directory = $this->path . '/' . dirname($relativePath);
        $filename = basename($relativePath);

        $path = $directory . '/' . $filename;
        if (file_exists($path)) {
            unlink($path);
        }

        $uploadedFile = $uploadedSource->getUploadedFile();

        return $uploadedFile->move($directory, $filename);
    }

    public function has(string $relativePath): bool
    {
        return file_exists($this->path . '/' . $relativePath);
    }
}
