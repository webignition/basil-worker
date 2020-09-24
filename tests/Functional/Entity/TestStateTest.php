<?php

declare(strict_types=1);

namespace App\Tests\Functional\Entity;

use App\Entity\TestState;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

class TestStateTest extends AbstractEntityTest
{
    public function testCreate()
    {
        $name = 'test-state-name';
        $state = TestState::create($name);

        self::assertNull($state->getId());
        self::assertSame($name, $state->getName());

        $this->entityManager->persist($state);
        $this->entityManager->flush();
        self::assertIsInt($state->getId());
    }

    public function testNameUniquenessEnforcedInDatabaseLayer()
    {
        $name = 'test-state-name';
        $state1 = TestState::create($name);
        $state2 = TestState::create($name);

        $this->entityManager->persist($state1);
        $this->entityManager->persist($state2);

        $this->expectException(UniqueConstraintViolationException::class);
        $this->expectExceptionMessage('Key (name)=(test-state-name) already exists');
        $this->entityManager->flush();
    }
}
