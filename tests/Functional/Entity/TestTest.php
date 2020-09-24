<?php

namespace App\Tests\Functional\Entity;

use App\Entity\Test;
use App\Entity\TestConfiguration;
use App\Entity\TestState;

class TestTest extends AbstractEntityTest
{
    public function testCreate()
    {
        $configuration = TestConfiguration::create('chrome', 'http://example.com');
        $this->entityManager->persist($configuration);
        $this->entityManager->flush();
        self::assertNotNull($configuration->getId());

        $state = TestState::create('test-state-name');
        $this->entityManager->persist($state);
        $this->entityManager->flush();
        self::assertNotNull($state->getId());

        $source = '/app/basil/Test/test.yml';
        $target = '/app/generated/Generated9bafa287f3df934f24c7855070da80f7.php';
        $stepCount = 3;

        $test = Test::create($configuration, $state, $source, $target, $stepCount);
        self::assertNull($test->getId());
        self::assertSame($configuration, $test->getConfiguration());
        self::assertSame($state, $test->getState());
        self::assertSame($source, $test->getSource());
        self::assertSame($target, $test->getTarget());
        self::assertSame($stepCount, $test->getStepCount());

        $this->entityManager->persist($test);
        $this->entityManager->flush();
        self::assertIsInt($test->getId());
    }
}
