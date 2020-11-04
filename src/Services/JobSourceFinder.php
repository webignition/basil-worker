<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Test;
use App\Repository\TestRepository;

class JobSourceFinder
{
    private JobStore $jobStore;
    private SourcePathTranslator $sourcePathTranslator;
    private TestRepository $testRepository;

    public function __construct(
        JobStore $jobStore,
        SourcePathTranslator $sourcePathTranslator,
        TestRepository $testRepository
    ) {
        $this->jobStore = $jobStore;
        $this->sourcePathTranslator = $sourcePathTranslator;
        $this->testRepository = $testRepository;
    }

    /**
     * @return string[]
     */
    public function findCompiledSources(): array
    {
        $compiledSources = [];
        $this->iterateJobSources(function (string $currentSource, bool $sourceHasTest) use (&$compiledSources): void {
            if (true === $sourceHasTest) {
                $compiledSources[] = $currentSource;
            }
        });

        return $compiledSources;
    }

    public function findNextNonCompiledSource(): ?string
    {
        $source = null;
        $this->iterateJobSources(function (string $currentSource, bool $sourceHasTest) use (&$source): bool {
            $source = false === $sourceHasTest ? $currentSource : null;

            return is_string($source);
        });

        return $source;
    }

    private function iterateJobSources(callable $something): void
    {
        if (false === $this->jobStore->hasJob()) {
            return;
        }

        $job = $this->jobStore->getJob();

        foreach ($job->getSources() as $currentSource) {
            $testSource = $this->sourcePathTranslator->translateJobSourceToTestSource($currentSource);
            $sourceHasTest = $this->testRepository->findBySource($testSource) instanceof Test;

            $bar = $something($currentSource, $sourceHasTest);

            if (true === $bar) {
                return;
            }
        }
    }
}
