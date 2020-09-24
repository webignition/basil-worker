<?php

namespace App\Tests\Functional\Entity;

use App\Entity\TestState;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

class TestStateTest extends AbstractEntityTest
{
    public function testCreate()
    {
        $name = 'test-state-name';
        $state = TestState::create($name);

        self::assertNull($state->getId());
        self::assertSame($name, $state->getName());

        if ($this->entityManager instanceof EntityManagerInterface) {
            $this->entityManager->persist($state);
            $this->entityManager->flush();
        }

        self::assertIsInt($state->getId());
    }

    public function testNameUniquenessEnforcedInDatabaseLayer()
    {
        $name = 'test-state-name';
        $state1 = TestState::create($name);
        $state2 = TestState::create($name);

        $this->expectException(UniqueConstraintViolationException::class);
        $this->expectExceptionMessage('Key (name)=(test-state-name) already exists');

        if ($this->entityManager instanceof EntityManagerInterface) {
            $this->entityManager->persist($state1);
            $this->entityManager->persist($state2);

            $this->entityManager->flush();
        }
    }
}
