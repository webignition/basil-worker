<?php

declare(strict_types=1);

namespace App\Services\EntityFactory;

use App\Entity\EntityInterface;
use App\Services\EntityPersister;

abstract class AbstractEntityFactory
{
    public function __construct(private EntityPersister $persister)
    {
    }

    protected function persist(EntityInterface $entity): EntityInterface
    {
        return $this->persister->persist($entity);
    }
}
