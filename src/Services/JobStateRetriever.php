<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\JobState;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class JobStateRetriever
{
    /**
     * @var EntityRepository<JobState>
     */
    private EntityRepository $repository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->repository = $entityManager->getRepository(JobState::class);
    }

    public function retrieve(string $name): ?JobState
    {
        return $this->repository->findOneBy([
            'name' => $name,
        ]);
    }
}
