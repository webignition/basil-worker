<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Test;
use App\Entity\TestConfiguration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use webignition\BasilCompilerModels\TestManifest;

class TestStore
{
    private EntityManagerInterface $entityManager;

    /**
     * @var EntityRepository<Test>
     */
    private EntityRepository $repository;
    private TestConfigurationStore $testConfigurationStore;

    public function __construct(EntityManagerInterface $entityManager, TestConfigurationStore $testConfigurationStore)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(Test::class);
        $this->testConfigurationStore = $testConfigurationStore;
    }

    /**
     * @return Test[]
     */
    public function findAll(): array
    {
        return $this->repository->findBy([], [
            'position' => 'ASC',
        ]);
    }

    public function findBySource(string $source): ?Test
    {
        return $this->repository->findOneBy([
            'source' => $source,
        ]);
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
        $test = $this->repository->findOneBy(
            [
                'state' => Test::STATE_AWAITING,
            ],
            [
                'position' => 'ASC',
            ]
        );

        return $test instanceof Test ? $test : null;
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
        $test = $this->repository->findOneBy([], [
            'position' => 'DESC',
        ]);

        return $test instanceof Test
            ? $test->getPosition()
            : null;
    }
}
