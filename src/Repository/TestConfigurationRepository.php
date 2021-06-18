<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use webignition\BasilWorker\PersistenceBundle\Entity\TestConfiguration;

/**
 * @method TestConfiguration|null find($id, $lockMode = null, $lockVersion = null)
 * @method TestConfiguration|null findOneBy(array $criteria, array $orderBy = null)
 * @method TestConfiguration[]    findAll()
 * @method TestConfiguration[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends ServiceEntityRepository<TestConfiguration>
 */
class TestConfigurationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TestConfiguration::class);
    }

    public function findOneByConfiguration(TestConfiguration $configuration): ?TestConfiguration
    {
        return $this->findOneBy([
            'browser' => $configuration->getBrowser(),
            'url' => $configuration->getUrl(),
        ]);
    }
}
