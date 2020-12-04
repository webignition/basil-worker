<?php

declare(strict_types=1);

namespace App\Services;

use App\Exception\MissingTestSourceException;
use App\Model\Manifest;
use App\Model\UploadedSource;
use App\Model\UploadedSourceCollection;

class SourceFactory
{
    private SourceStore $sourceStore;

    public function __construct(SourceStore $sourceStore)
    {
        $this->sourceStore = $sourceStore;
    }

    /**
     * @param Manifest $manifest
     * @param UploadedSourceCollection $uploadedSources
     *
     * @return string[]
     *
     * @throws MissingTestSourceException
     */
    public function createCollectionFromManifest(Manifest $manifest, UploadedSourceCollection $uploadedSources): array
    {
        $manifestTestPaths = $manifest->getTestPaths();
        $storedTestPaths = [];

        foreach ($manifestTestPaths as $manifestTestPath) {
            if (false === $uploadedSources->contains($manifestTestPath)) {
                throw new MissingTestSourceException($manifestTestPath);
            }

            $uploadedSource = $uploadedSources[$manifestTestPath];
            if (!$uploadedSource instanceof UploadedSource) {
                throw new MissingTestSourceException($manifestTestPath);
            }

            $this->sourceStore->store($uploadedSource, $manifestTestPath);
            $storedTestPaths[] = $manifestTestPath;
        }

        return $storedTestPaths;
    }
}
