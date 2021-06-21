<?php

declare(strict_types=1);

namespace App\Tests\Functional\Entity;

use App\Entity\Test;
use App\Entity\TestConfiguration;

class TestTest extends AbstractEntityTest
{
    public function testEntityMapping(): void
    {
        $repository = $this->entityManager->getRepository(Test::class);
        self::assertCount(0, $repository->findAll());

        $configuration = TestConfiguration::create('chrome', 'http://example.com');
        $this->entityManager->persist($configuration);
        $this->entityManager->flush();

        $test = Test::create($configuration, '/app/source/Test/test.yml', '/app/tests/GeneratedTest.php', 1, 1);
        $this->entityManager->persist($test);
        $this->entityManager->flush();

        self::assertCount(1, $repository->findAll());
    }
}
