<?php

declare(strict_types=1);

namespace App\Tests\Services;

use Doctrine\ORM\EntityManagerInterface;

class EntityRetriever
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EntityRefresher $entityRefresher,
    ) {
    }

    /**
     * @param class-string $className
     */
    public function find(string $className, mixed $id): ?object
    {
        $this->refresh();
        $repository = $this->entityManager->getRepository($className);

        return $repository->find($id);
    }

    /**
     * @param class-string $className
     */
    public function findByIndex(string $className, int $index): ?object
    {
        $this->refresh();
        $repository = $this->entityManager->getRepository($className);
        $entities = $repository->findAll();

        return array_key_exists($index, $entities) ? $entities[$index] : null;
    }

    /**
     * @param class-string $className
     */
    public function first(string $className): ?object
    {
        return $this->findByIndex($className, 0);
    }

    private function refresh(): void
    {
        $this->entityRefresher->refresh();
    }
}
