<?php

declare(strict_types=1);

namespace App\Tests\Services;

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

    public function create(EnvironmentSetup $setup): void
    {
        $jobSetup = $setup->getJobSetup();

        $this->jobFactory->create(
            $jobSetup->getLabel(),
            $jobSetup->getCallbackUrl(),
            $jobSetup->getMaximumDurationInSeconds()
        );

        foreach ($setup->getSourceSetups() as $sourceSetup) {
            $this->sourceFactory->create($sourceSetup->getType(), $sourceSetup->getPath());
        }

        foreach ($setup->getTestSetups() as $testSetup) {
            $this->testTestFactory->create($testSetup);
        }
    }
}
