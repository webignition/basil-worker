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

    public function find(int $testId): ?Test
    {
        return $this->repository->find($testId);
    }

    /**
     * @return Test[]
     */
    public function findAll(): array
    {
        return $this->repository->findAll();
    }

    public function findBySource(string $source): ?Test
    {
        return $this->repository->findBySource($source);
    }

    public function store(Test $test): Test
    {
        $this->entityManager->persist($test);
        $this->entityManager->flush();

        return $test;
    }

    public function findNextAwaiting(): ?Test
    {
        return $this->repository->findNextAwaiting();
    }

    public function getTotalCount(): int
    {
        return $this->repository->count([]);
    }

    public function getAwaitingCount(): int
    {
        return $this->repository->getAwaitingCount();
    }
}
