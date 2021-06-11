<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Model\UploadedFileKey;
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

    public function create(string $path): UploadedFile
    {
        return new UploadedFile($path, '', 'text/yaml', null, true);
    }

    /**
     * @param string[] $paths
     *
     * @return array<string, UploadedFile>
     */
    public function createCollection(array $paths): array
    {
        $collection = [];

        foreach ($paths as $fixturePath => $uploadPath) {
            $uploadedFile = $this->create($uploadPath);
            $key = new UploadedFileKey($fixturePath);
            $collection[$key->encode()] = $uploadedFile;
        }

        return $collection;
    }
}
