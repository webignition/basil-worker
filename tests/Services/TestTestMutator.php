<?php

declare(strict_types=1);

namespace App\Tests\Services;

use Doctrine\ORM\EntityManagerInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Test;

class TestTestMutator
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @param Test::STATE_* $state
     */
    public function setState(Test $test, string $state): Test
    {
        $test->setState($state);
        $this->entityManager->persist($test);
        $this->entityManager->flush();

        return $test;
    }
}
