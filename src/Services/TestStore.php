<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Test;
use App\Repository\TestRepository;
use Doctrine\ORM\EntityManagerInterface;

class TestStore
{
    private EntityManagerInterface $entityManager;
    private TestRepository $repository;

    public function __construct(EntityManagerInterface $entityManager, TestRepository $testRepository)
    {
        $this->entityManager = $entityManager;
        $this->repository = $testRepository;
    }

    public function store(Test $test): Test
    {
        $this->entityManager->persist($test);
        $this->entityManager->flush();

        return $test;
    }
}
