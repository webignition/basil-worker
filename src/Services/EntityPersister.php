<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\EntityInterface;
use Doctrine\ORM\EntityManagerInterface;

class EntityPersister
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function persist(EntityInterface $entity): EntityInterface
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        return $entity;
    }
}
