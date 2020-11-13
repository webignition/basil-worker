<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\CallbackEntity;
use Doctrine\ORM\EntityManagerInterface;

class CallbackStore
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function store(CallbackEntity $callback): CallbackEntity
    {
        $this->entityManager->persist($callback);
        $this->entityManager->flush();

        return $callback;
    }
}
