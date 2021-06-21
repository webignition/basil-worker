<?php

declare(strict_types=1);

namespace App\Services\EntityFactory;

use App\Entity\Test;
use App\Entity\TestConfiguration;
use App\Repository\TestRepository;
use App\Services\EntityPersister;
use App\Services\EntityStore\TestConfigurationStore;

class TestFactory extends AbstractEntityFactory
{
    public function __construct(
        EntityPersister $persister,
        private TestRepository $repository,
        private TestConfigurationStore $configurationStore
    ) {
        parent::__construct($persister);
    }

    public function create(
        TestConfiguration $configuration,
        string $source,
        string $target,
        int $stepCount
    ): Test {
        $test = Test::create(
            $this->configurationStore->get($configuration),
            $source,
            $target,
            $stepCount,
            $this->findNextPosition()
        );

        $this->persist($test);

        return $test;
    }

    private function findNextPosition(): int
    {
        return $this->repository->findMaxPosition() + 1;
    }
}
