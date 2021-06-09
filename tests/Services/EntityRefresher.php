<?php

declare(strict_types=1);

namespace App\Tests\Services;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;

class EntityRefresher
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EntityClassNames $entityClassNames
    ) {
    }

    public function refresh(): void
    {
        foreach ($this->entityClassNames->get() as $className) {
            $this->refreshForEntity($className);
        }
    }

    /**
     * @param class-string $className
     */
    private function refreshForEntity(string $className): void
    {
        $repository = $this->entityManager->getRepository($className);
        if ($repository instanceof ObjectRepository) {
            $entities = $repository->findAll();
            foreach ($entities as $entity) {
                $this->entityManager->refresh($entity);
            }
        }
    }
}
