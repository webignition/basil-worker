<?php

declare(strict_types=1);

namespace App\Tests\Services;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadedFileFactory
{
    public function createForManifest(string $path): UploadedFile
    {
        return new UploadedFile(
            $path,
            'manifest.yml',
            'text/yaml',
            null,
            true
        );
    }
}
