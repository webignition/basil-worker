<?php

declare(strict_types=1);

namespace App\Services;

use App\Exception\MissingTestSourceException;
use App\Model\Manifest;
use App\Model\UploadedSource;
use App\Model\UploadedSourceCollection;
use App\Entity\Source;
use App\Services\EntityFactory\SourceFactory as SourceEntityFactory;

class SourceFactory
{
    private SourceFileStore $sourceFileStore;
    private SourceEntityFactory $sourceEntityFactory;

    public function __construct(SourceFileStore $sourceFileStore, SourceEntityFactory $bundleSourceFactory)
    {
        $this->sourceFileStore = $sourceFileStore;
        $this->sourceEntityFactory = $bundleSourceFactory;
    }

    /**
     * @throws MissingTestSourceException
     */
    public function createCollectionFromManifest(Manifest $manifest, UploadedSourceCollection $uploadedSources): void
    {
        $manifestTestPaths = $manifest->getTestPaths();

        foreach ($manifestTestPaths as $manifestTestPath) {
            if (false === $uploadedSources->contains($manifestTestPath)) {
                throw new MissingTestSourceException($manifestTestPath);
            }

            $uploadedSource = $uploadedSources[$manifestTestPath];
            if (!$uploadedSource instanceof UploadedSource) {
                throw new MissingTestSourceException($manifestTestPath);
            }
        }

        foreach ($uploadedSources as $uploadedSource) {
            /** @var UploadedSource $uploadedSource */
            $uploadedSourceRelativePath = $uploadedSource->getPath();
            $sourceType = Source::TYPE_RESOURCE;

            if ($manifest->isTestPath($uploadedSourceRelativePath)) {
                $sourceType = Source::TYPE_TEST;
            }

            $this->sourceFileStore->store($uploadedSource, $uploadedSourceRelativePath);

            $this->sourceEntityFactory->create($sourceType, $uploadedSourceRelativePath);
        }
    }
}
