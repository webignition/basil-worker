<?php

declare(strict_types=1);

namespace App\Tests\Functional\Entity;

use App\Entity\Job;

class JobTest extends AbstractEntityTest
{
    public function testEntityMapping(): void
    {
        $repository = $this->entityManager->getRepository(Job::class);
        self::assertCount(0, $repository->findAll());

        $job = Job::create('label content', 'http://example.com/callback', 600);

        $this->entityManager->persist($job);
        $this->entityManager->flush();

        self::assertCount(1, $repository->findAll());
    }
}
