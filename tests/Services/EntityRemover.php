<?php

declare(strict_types=1);

namespace App\Tests\Services;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;

class EntityRemover
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EntityClassNames $entityClassNames,
    ) {
    }

    public function removeAll(): void
    {
        foreach ($this->entityClassNames->get() as $className) {
            $this->removeForEntity($className);
        }
    }

    /**
     * @param class-string $className
     */
    private function removeForEntity(string $className): void
    {
        $repository = $this->entityManager->getRepository($className);
        if ($repository instanceof ObjectRepository) {
            $entities = $repository->findAll();

            foreach ($entities as $entity) {
                $this->entityManager->remove($entity);
                $this->entityManager->flush();
            }
        }
    }
}
