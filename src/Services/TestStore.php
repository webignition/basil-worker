<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Test;
use App\Entity\TestConfiguration;
use App\Repository\TestRepository;
use Doctrine\ORM\EntityManagerInterface;
use webignition\BasilCompilerModels\TestManifest;

class TestStore
{
    private EntityManagerInterface $entityManager;
    private TestRepository $repository;
    private TestConfigurationStore $testConfigurationStore;

    public function __construct(
        EntityManagerInterface $entityManager,
        TestConfigurationStore $testConfigurationStore,
        TestRepository $testRepository
    ) {
        $this->entityManager = $entityManager;
        $this->testConfigurationStore = $testConfigurationStore;
        $this->repository = $testRepository;
    }

    public function find(int $testId): ?Test
    {
        return $this->repository->find($testId);
    }

    /**
     * @return Test[]
     */
    public function findAll(): array
    {
        return $this->repository->findAll();
    }

    public function findBySource(string $source): ?Test
    {
        return $this->repository->findBySource($source);
    }

    public function create(
        TestConfiguration $configuration,
        string $source,
        string $target,
        int $stepCount
    ): Test {
        $position = $this->findNextPosition();
        $configuration = $this->testConfigurationStore->findByConfiguration($configuration);
        $test = Test::create($configuration, $source, $target, $stepCount, $position);

        return $this->store($test);
    }

    public function createFromTestManifest(TestManifest $manifest): Test
    {
        $manifestConfiguration = $manifest->getConfiguration();

        return $this->create(
            TestConfiguration::create(
                $manifestConfiguration->getBrowser(),
                $manifestConfiguration->getUrl()
            ),
            $manifest->getSource(),
            $manifest->getTarget(),
            $manifest->getStepCount()
        );
    }

    /**
     * @param TestManifest[] $manifests
     *
     * @return Test[]
     */
    public function createFromTestManifests(array $manifests): array
    {
        $tests = [];

        foreach ($manifests as $manifest) {
            if ($manifest instanceof TestManifest) {
                $tests[] = $this->createFromTestManifest($manifest);
            }
        }

        return $tests;
    }

    public function store(Test $test): Test
    {
        $this->entityManager->persist($test);
        $this->entityManager->flush();

        return $test;
    }

    public function findNextAwaiting(): ?Test
    {
        return $this->repository->findNextAwaiting();
    }

    public function getTotalCount(): int
    {
        return $this->repository->count([]);
    }

    public function getAwaitingCount(): int
    {
        return $this->repository->getAwaitingCount();
    }

    private function findNextPosition(): int
    {
        $maxPosition = $this->findMaxPosition();

        return null === $maxPosition
            ? 1
            : $maxPosition + 1;
    }

    private function findMaxPosition(): ?int
    {
        return $this->repository->findMaxPosition();
    }
}
