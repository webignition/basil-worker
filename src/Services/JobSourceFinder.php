<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Test;

class JobSourceFinder
{
    private JobStore $jobStore;
    private TestStore $testStore;
    private SourcePathTranslator $sourcePathTranslator;

    public function __construct(JobStore $jobStore, TestStore $testStore, SourcePathTranslator $sourcePathTranslator)
    {
        $this->jobStore = $jobStore;
        $this->testStore = $testStore;
        $this->sourcePathTranslator = $sourcePathTranslator;
    }

    public function findNextNonCompiledSource(): ?string
    {
        if (false === $this->jobStore->hasJob()) {
            return null;
        }

        $job = $this->jobStore->getJob();
        foreach ($job->getSources() as $source) {
            $testSource = $this->sourcePathTranslator->translateJobSourceToTestSource($source);
            $hasTest = $this->testStore->findBySource($testSource) instanceof Test;

            if (false === $hasTest) {
                return $source;
            }
        }

        return null;
    }
}
