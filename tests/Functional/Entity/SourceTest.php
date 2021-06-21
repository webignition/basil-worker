<?php

declare(strict_types=1);

namespace App\Tests\Functional\Entity;

use App\Entity\Source;

class SourceTest extends AbstractEntityTest
{
    public function testEntityMapping(): void
    {
        $repository = $this->entityManager->getRepository(Source::class);
        self::assertCount(0, $repository->findAll());

        $source = Source::create(Source::TYPE_TEST, 'Test/test.yml');

        $this->entityManager->persist($source);
        $this->entityManager->flush();

        self::assertCount(1, $repository->findAll());
    }
}
