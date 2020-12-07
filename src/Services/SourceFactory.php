<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Source;
use App\Exception\MissingTestSourceException;
use App\Model\Manifest;
use App\Model\UploadedSource;
use App\Model\UploadedSourceCollection;
use Doctrine\ORM\EntityManagerInterface;

class SourceFactory
{
    private SourceFileStore $sourceFileStore;
    private EntityManagerInterface $entityManager;

    public function __construct(SourceFileStore $sourceFileStore, EntityManagerInterface $entityManager)
    {
        $this->sourceFileStore = $sourceFileStore;
        $this->entityManager = $entityManager;
    }

    /**
     * @param Manifest $manifest
     * @param UploadedSourceCollection $uploadedSources
     *
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
            /** @var UploadedSource$uploadedSource */

            $uploadedSourceRelativePath = $uploadedSource->getPath();
            $sourceType = Source::TYPE_RESOURCE;

            if ($manifest->isTestPath($uploadedSourceRelativePath)) {
                $sourceType = Source::TYPE_TEST;
            }

            $this->sourceFileStore->store($uploadedSource, $uploadedSourceRelativePath);

            $source = Source::create($sourceType, $uploadedSourceRelativePath);

            $this->entityManager->persist($source);
            $this->entityManager->flush();
        }
    }
}
