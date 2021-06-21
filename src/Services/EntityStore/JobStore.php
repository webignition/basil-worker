<?php

declare(strict_types=1);

namespace App\Services\EntityStore;

use App\Entity\Job;
use Doctrine\ORM\EntityManagerInterface;

class JobStore
{
    private Job $job;

    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function has(): bool
    {
        return $this->fetch() instanceof Job;
    }

    public function get(): Job
    {
        $job = $this->fetch();
        if ($job instanceof Job) {
            $this->job = $job;
        }

        return $this->job;
    }

    private function fetch(): ?Job
    {
        $job = $this->entityManager->find(Job::class, Job::ID);

        return $job instanceof Job ? $job : null;
    }
}
