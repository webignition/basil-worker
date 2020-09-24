<?php

namespace App\Tests\Functional\Entity;

use App\Entity\JobState;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

class JobStateTest extends AbstractEntityTest
{
    public function testCreate()
    {
        $name = 'job-state-name';
        $state = JobState::create($name);

        self::assertNull($state->getId());
        self::assertSame($name, $state->getName());

        $this->entityManager->persist($state);
        $this->entityManager->flush();

        self::assertIsInt($state->getId());
    }

    public function testNameUniquenessEnforcedInDatabaseLayer()
    {
        $name = 'job-state-name';
        $state1 = JobState::create($name);
        $state2 = JobState::create($name);

        $this->entityManager->persist($state1);
        $this->entityManager->persist($state2);

        $this->expectException(UniqueConstraintViolationException::class);
        $this->expectExceptionMessage('Key (name)=(job-state-name) already exists');

        $this->entityManager->flush();
    }
}
