<?php

declare(strict_types=1);

namespace App\Tests\Functional\Entity;

use App\Entity\Test;
use App\Services\TestConfigurationStore;

class TestTest extends AbstractEntityTest
{
    public function testCreate()
    {
        $testConfigurationStore = self::$container->get(TestConfigurationStore::class);
        self::assertInstanceOf(TestConfigurationStore::class, $testConfigurationStore);
        $configuration = $testConfigurationStore->find('chrome', 'http://example.com');

        $source = '/app/basil/Test/test.yml';
        $target = '/app/generated/Generated9bafa287f3df934f24c7855070da80f7.php';
        $stepCount = 3;
        $position = 1;
        $manifestPath = 'manifests/manifest-test.yml';

        $test = Test::create($configuration, $source, $target, $stepCount, $position, $manifestPath);
        self::assertNull($test->getId());
        self::assertSame($configuration, $test->getConfiguration());
        self::assertSame('awaiting', $test->getState());
        self::assertSame($source, $test->getSource());
        self::assertSame($target, $test->getTarget());
        self::assertSame($stepCount, $test->getStepCount());
        self::assertSame($position, $test->getPosition());
        self::assertSame($manifestPath, $test->getManifestPath());

        $this->entityManager->persist($test);
        $this->entityManager->flush();
        self::assertIsInt($test->getId());
    }
}
