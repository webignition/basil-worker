<?php

declare(strict_types=1);

namespace App\Services\EntityFactory;

use App\Services\EntityPersister;
use App\Entity\EntityInterface;

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
