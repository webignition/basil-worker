<?php

declare(strict_types=1);

namespace App\Services\EntityStore;

use App\Entity\TestConfiguration;
use App\Repository\TestConfigurationRepository;
use App\Services\EntityFactory\TestConfigurationFactory;

class TestConfigurationStore
{
    public function __construct(
        private TestConfigurationRepository $repository,
        private TestConfigurationFactory $factory
    ) {
    }

    public function get(TestConfiguration $testConfiguration): TestConfiguration
    {
        $existingConfiguration = $this->repository->findOneByConfiguration($testConfiguration);
        if ($existingConfiguration instanceof TestConfiguration) {
            return $existingConfiguration;
        }

        return $this->factory->create(
            $testConfiguration->getBrowser(),
            $testConfiguration->getUrl()
        );
    }
}
