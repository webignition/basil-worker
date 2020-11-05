<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Entity\Test;
use App\Entity\TestConfiguration;
use App\Services\TestFactory;
use webignition\BasilCompilerModels\TestManifest;
use webignition\BasilModels\Test\Configuration as ModelTestConfiguration;

class TestTestFactory
{
    private TestFactory $testFactory;

    public function __construct(TestFactory $testFactory)
    {
        $this->testFactory = $testFactory;
    }

    public function create(
        TestConfiguration $configuration,
        string $source,
        string $target,
        int $stepCount
    ): Test {
        $tests = $this->testFactory->createFromManifestCollection([
            new TestManifest(
                new ModelTestConfiguration($configuration->getBrowser(), $configuration->getUrl()),
                $source,
                $target,
                $stepCount
            ),
        ]);

        return $tests[0];
    }
}
