<?php

declare(strict_types=1);

namespace App\Tests\Services;

use webignition\BasilWorker\PersistenceBundle\Entity\Source;
use webignition\BasilWorker\PersistenceBundle\Services\Factory\SourceFactory as BundleSourceFactory;

class SourceFactory
{
    public function __construct(
        private BundleSourceFactory $bundleSourceFactory,
    ) {
    }

    public function createFromSourcePath(string $sourcePath): Source
    {
        $sourceType = 0 === substr_count($sourcePath, 'Test/')
            ? Source::TYPE_RESOURCE
            : Source::TYPE_TEST;

        return $this->bundleSourceFactory->create($sourceType, $sourcePath);
    }
}
