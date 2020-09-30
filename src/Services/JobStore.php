<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Job;
use Doctrine\ORM\EntityManagerInterface;

class JobStore
{
    private EntityManagerInterface $entityManager;
    private Job $job;
    private bool $hasJob = false;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $job = $entityManager->find(Job::class, Job::ID);

        if ($job instanceof Job) {
            $this->job = $job;
            $this->hasJob = true;
        }
    }

    public function create(string $label, string $callbackUrl): Job
    {
        $this->job = Job::create($label, $callbackUrl);
        $this->hasJob = true;

        $this->store();

        return $this->getJob();
    }

    public function hasJob(): bool
    {
        return $this->hasJob;
    }

    public function getJob(): Job
    {
        return $this->job;
    }

    public function store(): void
    {
        if ($this->hasJob) {
            $this->entityManager->persist($this->job);
            $this->entityManager->flush();
        }
    }
}
