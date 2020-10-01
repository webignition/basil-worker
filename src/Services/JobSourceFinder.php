<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Test;

class JobSourceFinder
{
    private JobStore $jobStore;
    private TestStore $testStore;

    public function __construct(JobStore $jobStore, TestStore $testStore)
    {
        $this->jobStore = $jobStore;
        $this->testStore = $testStore;
    }

    public function findNextNonCompiledSource(): ?string
    {
        if (false === $this->jobStore->hasJob()) {
            return null;
        }

        $job = $this->jobStore->getJob();
        foreach ($job->getSources() as $source) {
            $hasTest = $this->testStore->findBySource($source) instanceof Test;

            if (false === $hasTest) {
                return $source;
            }
        }

        return null;
    }
}
