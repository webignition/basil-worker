<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Test;
use App\Entity\TestConfiguration;
use App\Repository\TestRepository;
use webignition\BasilCompilerModels\TestManifest;

class TestFactory
{
    private TestStore $testStore;
    private TestRepository $testRepository;
    private TestConfigurationStore $testConfigurationStore;

    public function __construct(
        TestStore $testStore,
        TestRepository $testRepository,
        TestConfigurationStore $testConfigurationStore
    ) {
        $this->testStore = $testStore;
        $this->testRepository = $testRepository;
        $this->testConfigurationStore = $testConfigurationStore;
    }

    /**
     * @param TestManifest[] $manifests
     *
     * @return Test[]
     */
    public function createFromManifestCollection(array $manifests): array
    {
        $tests = [];

        foreach ($manifests as $manifest) {
            if ($manifest instanceof TestManifest) {
                $tests[] = $this->createFromManifest($manifest);
            }
        }

        return $tests;
    }

    private function createFromManifest(TestManifest $manifest): Test
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

    protected function create(
        TestConfiguration $configuration,
        string $source,
        string $target,
        int $stepCount
    ): Test {
        $position = $this->findNextPosition();
        $configuration = $this->testConfigurationStore->findByConfiguration($configuration);
        $test = Test::create($configuration, $source, $target, $stepCount, $position);

        return $this->testStore->store($test);
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
        return $this->testRepository->findMaxPosition();
    }
}
