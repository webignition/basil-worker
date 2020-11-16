<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Callback\CallbackInterface;
use Doctrine\ORM\EntityManagerInterface;

class CallbackStore
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function store(CallbackInterface $callback): CallbackInterface
    {
        $this->entityManager->persist($callback->getEntity());
        $this->entityManager->flush();

        return $callback;
    }
}
