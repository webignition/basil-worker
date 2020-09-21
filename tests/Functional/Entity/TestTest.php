<?php

namespace App\Tests\Functional\Entity;

use App\Entity\Test;
use App\Entity\TestConfiguration;
use Doctrine\ORM\EntityManagerInterface;

class TestTest extends AbstractEntityTest
{
    public function testCreate()
    {
        $configuration = TestConfiguration::create('chrome', 'http://example.com');

        if ($this->entityManager instanceof EntityManagerInterface) {
            $this->entityManager->persist($configuration);
            $this->entityManager->flush();
        }

        $source = '/app/basil/Test/test.yml';
        $target = '/app/generated/Generated9bafa287f3df934f24c7855070da80f7.php';
        $stepCount = 3;

        $test = Test::create($configuration, $source, $target, $stepCount);
        self::assertNotNull($configuration->getId());
        self::assertNull($test->getId());
        self::assertSame($configuration, $test->getConfiguration());
        self::assertSame($source, $test->getSource());
        self::assertSame($target, $test->getTarget());
        self::assertSame($stepCount, $test->getStepCount());

        if ($this->entityManager instanceof EntityManagerInterface) {
            $this->entityManager->persist($test);
            $this->entityManager->flush();
        }

        self::assertIsInt($test->getId());
    }
}
