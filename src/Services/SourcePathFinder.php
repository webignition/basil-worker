<?php

declare(strict_types=1);

namespace App\Services;

use App\Repository\SourceRepository;
use App\Repository\TestRepository;

class SourcePathFinder
{
    private TestRepository $testRepository;
    private SourceRepository $sourceRepository;

    public function __construct(TestRepository $testRepository, SourceRepository $sourceRepository)
    {
        $this->testRepository = $testRepository;
        $this->sourceRepository = $sourceRepository;
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
        $sourcePaths = $this->sourceRepository->findAllRelativePaths();
        $testPaths = $this->testRepository->findAllRelativeSources();

        foreach ($sourcePaths as $sourcePath) {
            if (!in_array($sourcePath, $testPaths)) {
                return $sourcePath;
            }
        }

        return null;
    }
}
