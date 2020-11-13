<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\CallbackEntityInterface;
use Doctrine\ORM\EntityManagerInterface;

class CallbackStore
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function store(CallbackEntityInterface $callback): CallbackEntityInterface
    {
        $this->entityManager->persist($callback);
        $this->entityManager->flush();

        return $callback;
    }
}
