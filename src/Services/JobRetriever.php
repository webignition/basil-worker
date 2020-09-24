<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Job;
use Doctrine\ORM\EntityManagerInterface;

class JobRetriever
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function retrieve(): ?Job
    {
        return $this->entityManager->find(Job::class, Job::ID);
    }
}
