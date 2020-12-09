<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\AbstractBaseFunctionalTest;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackEntity;
use webignition\BasilWorker\PersistenceBundle\Entity\Job;
use webignition\BasilWorker\PersistenceBundle\Entity\Source;
use webignition\BasilWorker\PersistenceBundle\Entity\Test;
use webignition\BasilWorker\PersistenceBundle\Entity\TestConfiguration;

abstract class AbstractBaseIntegrationTest extends AbstractBaseFunctionalTest
{
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $entityManager = self::$container->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        if ($entityManager instanceof EntityManagerInterface) {
            $this->entityManager = $entityManager;
        }

        $this->removeAllEntities(Job::class);
        $this->removeAllEntities(Test::class);
        $this->removeAllEntities(TestConfiguration::class);
        $this->removeAllEntities(CallbackEntity::class);
        $this->removeAllEntities(Source::class);
    }

    protected function tearDown(): void
    {
        $this->removeAllEntities(Job::class);
        $this->removeAllEntities(Test::class);
        $this->removeAllEntities(TestConfiguration::class);
        $this->removeAllEntities(CallbackEntity::class);
        $this->removeAllEntities(Source::class);

        parent::tearDown();
    }

    /**
     * @param class-string $entityClassName
     */
    private function removeAllEntities(string $entityClassName): void
    {
        $repository = $this->entityManager->getRepository($entityClassName);
        if ($repository instanceof ObjectRepository) {
            $entities = $repository->findAll();

            foreach ($entities as $entity) {
                $this->entityManager->remove($entity);
                $this->entityManager->flush();
            }
        }
    }
}
