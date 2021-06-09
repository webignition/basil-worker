<?php

declare(strict_types=1);

namespace App\Tests\Services;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;

class EntityRemover
{
    /**
     * @param array<class-string> $entityClassNames
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private array $entityClassNames,
    ) {
    }

    public function removeAll(): void
    {
        foreach ($this->entityClassNames as $className) {
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
