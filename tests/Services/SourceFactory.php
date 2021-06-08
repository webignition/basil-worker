<?php

declare(strict_types=1);

namespace App\Tests\Services;

use webignition\BasilWorker\PersistenceBundle\Entity\Source;
use webignition\BasilWorker\PersistenceBundle\Services\Factory\SourceFactory as BundleSourceFactory;

class SourceFactory
{
    public function __construct(
        private BasilFixtureHandler $basilFixtureHandler,
        private BundleSourceFactory $bundleSourceFactory,
    ) {
    }

    /**
     * @return array<string, Source>
     */
    public function createFromManifestPath(string $manifestPath): array
    {
        $manifestContent = (string) file_get_contents($manifestPath);
        $sourcePaths = array_filter(explode("\n", $manifestContent));

        $this->basilFixtureHandler->createUploadFileCollection($sourcePaths);

        $sources = [];
        foreach ($sourcePaths as $sourcePath) {
            $sourceType = substr_count($sourcePath, 'Test/') === 0
                ? Source::TYPE_RESOURCE
                : Source::TYPE_TEST;

            $sources[$sourcePath] = $this->bundleSourceFactory->create($sourceType, $sourcePath);
        }

        return $sources;
    }
}
