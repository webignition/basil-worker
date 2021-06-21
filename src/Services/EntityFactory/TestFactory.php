<?php

declare(strict_types=1);

namespace App\Services\EntityFactory;

use App\Repository\TestRepository;
use App\Services\EntityPersister;
use App\Services\EntityStore\TestConfigurationStore;
use webignition\BasilWorker\PersistenceBundle\Entity\Test;
use webignition\BasilWorker\PersistenceBundle\Entity\TestConfiguration;

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
