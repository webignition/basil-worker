<?php

namespace App\Services\FixtureLoader;

use App\Services\DataProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;

abstract class AbstractFixtureLoader
{
    protected EntityManagerInterface $entityManager;

    /**
     * @var ObjectRepository<mixed>
     */
    protected ObjectRepository $repository;

    /**
     * @var array<mixed>
     */
    protected array $data;

    public function __construct(EntityManagerInterface $entityManager, DataProviderInterface $dataProvider)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository($this->getEntityClass());
        $this->data = $dataProvider->getData();
    }

    abstract protected function getEntityClass(): string;
}
