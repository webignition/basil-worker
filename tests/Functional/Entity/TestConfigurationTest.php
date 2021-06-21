<?php

declare(strict_types=1);

namespace App\Tests\Functional\Entity;

use App\Entity\TestConfiguration;

class TestConfigurationTest extends AbstractEntityTest
{
    public function testEntityMapping(): void
    {
        $repository = $this->entityManager->getRepository(TestConfiguration::class);
        self::assertCount(0, $repository->findAll());

        $configuration = TestConfiguration::create('chrome', 'http://example.com');

        $this->entityManager->persist($configuration);
        $this->entityManager->flush();

        self::assertCount(1, $repository->findAll());
    }
}
