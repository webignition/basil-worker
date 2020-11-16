<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\StorableCallbackInterface;
use Doctrine\ORM\EntityManagerInterface;

class CallbackStore
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function store(StorableCallbackInterface $callback): StorableCallbackInterface
    {
        $this->entityManager->persist($callback->getEntity());
        $this->entityManager->flush();

        return $callback;
    }
}
