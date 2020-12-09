<?php

declare(strict_types=1);

namespace App\Services;

use App\Repository\TestRepository;
use webignition\BasilWorker\PersistenceBundle\Services\Store\SourceStore;

class SourcePathFinder
{
    private TestRepository $testRepository;
    private SourceStore $sourceStore;

    public function __construct(TestRepository $testRepository, SourceStore $sourceStore)
    {
        $this->testRepository = $testRepository;
        $this->sourceStore = $sourceStore;
    }

    /**
     * @return string[]
     */
    public function findCompiledPaths(): array
    {
        return $this->testRepository->findAllRelativeSources();
    }

    public function findNextNonCompiledPath(): ?string
    {
        $sourcePaths = $this->sourceStore->findAllPaths();
        $testPaths = $this->testRepository->findAllRelativeSources();

        foreach ($sourcePaths as $sourcePath) {
            if (!in_array($sourcePath, $testPaths)) {
                return $sourcePath;
            }
        }

        return null;
    }
}
