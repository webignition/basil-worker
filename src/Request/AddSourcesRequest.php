<?php

declare(strict_types=1);

namespace App\Request;

use App\Model\Manifest;
use App\Model\UploadedSource;
use App\Model\UploadedSourceCollection;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

class AddSourcesRequest extends AbstractEncapsulatingRequest
{
    public const KEY_MANIFEST = 'manifest';

    private ?Manifest $manifest;
    private UploadedSourceCollection $uploadedSources;

    public function processRequest(Request $request): void
    {
        $files = $request->files;

        $manifest = $files->get(self::KEY_MANIFEST);
        $this->manifest = $manifest instanceof UploadedFile ? new Manifest($manifest) : null;

        $files->remove(self::KEY_MANIFEST);

        $uploadedSources = [];
        foreach ($files as $path => $file) {
            if ($file instanceof UploadedFile) {
                $uploadedSources[$path] = new UploadedSource($path, $file);
            }
        }

        $this->uploadedSources = new UploadedSourceCollection($uploadedSources);
    }

    public function getManifest(): ?Manifest
    {
        return $this->manifest;
    }

    public function getUploadedSources(): UploadedSourceCollection
    {
        return $this->uploadedSources;
    }
}
