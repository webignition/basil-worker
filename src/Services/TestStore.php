<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Test;
use Doctrine\ORM\EntityManagerInterface;

class TestStore
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function store(Test $test): Test
    {
        $this->entityManager->persist($test);
        $this->entityManager->flush();

        return $test;
    }
}
