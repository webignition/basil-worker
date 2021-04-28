<?php

declare(strict_types=1);

namespace App\Request;

use App\Model\Manifest;
use App\Model\UploadedFileKey;
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

        $manifestKey = new UploadedFileKey(self::KEY_MANIFEST);
        $encodedManifestKey = $manifestKey->encode();

        $manifest = $files->get($encodedManifestKey);
        $this->manifest = $manifest instanceof UploadedFile ? new Manifest($manifest) : null;

        $files->remove($encodedManifestKey);

        $uploadedSources = [];
        foreach ($files as $encodedKey => $file) {
            $key = UploadedFileKey::fromEncodedKey($encodedKey);
            $path = (string) $key;

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
