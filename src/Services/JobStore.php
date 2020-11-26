<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Job;
use Doctrine\ORM\EntityManagerInterface;

class JobStore
{
    private EntityManagerInterface $entityManager;
    private Job $job;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function create(string $label, string $callbackUrl, int $maximumDurationInSeconds): Job
    {
        $job = Job::create($label, $callbackUrl, $maximumDurationInSeconds);
        $this->store($job);

        return $job;
    }

    public function hasJob(): bool
    {
        return null !== $this->fetch();
    }

    public function getJob(): Job
    {
        $job = $this->fetch();
        if ($job instanceof Job) {
            $this->job = $job;
        }

        return $this->job;
    }

    public function store(Job $job): void
    {
        $this->entityManager->persist($job);
        $this->entityManager->flush();
    }

    private function fetch(): ?Job
    {
        return $this->entityManager->find(Job::class, Job::ID);
    }
}
