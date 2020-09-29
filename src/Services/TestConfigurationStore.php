<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\TestConfiguration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class TestConfigurationStore
{
    private EntityManagerInterface $entityManager;

    /**
     * @var EntityRepository<TestConfiguration>
     */
    private EntityRepository $repository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(TestConfiguration::class);
    }

    public function find(string $browser, string $url): TestConfiguration
    {
        $testConfiguration = $this->repository->findOneBy([
            'browser' => $browser,
            'url' => $url,
        ]);

        if (!$testConfiguration instanceof TestConfiguration) {
            $testConfiguration = TestConfiguration::create($browser, $url);
            $this->store($testConfiguration);
        }

        return $testConfiguration;
    }

    private function store(TestConfiguration $testConfiguration): TestConfiguration
    {
        $this->entityManager->persist($testConfiguration);
        $this->entityManager->flush();

        return $testConfiguration;
    }
}
