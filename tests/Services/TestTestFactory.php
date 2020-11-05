<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Entity\Test;
use App\Entity\TestConfiguration;
use App\Services\TestFactory;
use App\Services\TestStore;
use webignition\BasilCompilerModels\TestManifest;
use webignition\BasilModels\Test\Configuration as ModelTestConfiguration;

class TestTestFactory
{
    private TestFactory $testFactory;
    private TestStore $testStore;

    public function __construct(TestFactory $testFactory, TestStore $testStore)
    {
        $this->testFactory = $testFactory;
        $this->testStore = $testStore;
    }

    /**
     * @param TestConfiguration $configuration
     * @param string $source
     * @param string $target
     * @param int $stepCount
     * @param Test::STATE_* $state
     *
     * @return Test
     */
    public function create(
        TestConfiguration $configuration,
        string $source,
        string $target,
        int $stepCount,
        string $state = Test::STATE_AWAITING
    ): Test {
        $tests = $this->testFactory->createFromManifestCollection([
            new TestManifest(
                new ModelTestConfiguration($configuration->getBrowser(), $configuration->getUrl()),
                $source,
                $target,
                $stepCount
            ),
        ]);

        $test = $tests[0];
        $test->setState($state);
        $this->testStore->store($test);

        return $test;
    }
}
