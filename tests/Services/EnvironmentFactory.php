<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Tests\Model\Environment;
use App\Tests\Model\EnvironmentSetup;
use webignition\BasilWorker\PersistenceBundle\Services\Factory\JobFactory;
use webignition\BasilWorker\PersistenceBundle\Services\Factory\SourceFactory;

class EnvironmentFactory
{
    public function __construct(
        private JobFactory $jobFactory,
        private SourceFactory $sourceFactory,
        private TestTestFactory $testTestFactory,
    ) {
    }

    public function create(EnvironmentSetup $setup): Environment
    {
        $jobSetup = $setup->getJobSetup();

        $job = $this->jobFactory->create(
            $jobSetup->getLabel(),
            $jobSetup->getCallbackUrl(),
            $jobSetup->getMaximumDurationInSeconds()
        );

        $sources = [];
        foreach ($setup->getSourceSetups() as $sourceSetup) {
            $sources[] = $this->sourceFactory->create($sourceSetup->getType(), $sourceSetup->getPath());
        }

        $tests = [];
        foreach ($setup->getTestSetups() as $testSetup) {
            $tests[] = $this->testTestFactory->create($testSetup);
        }

        return (new Environment())
            ->withJob($job)
            ->withSources($sources)
            ->withTests($tests)
        ;
    }
}
