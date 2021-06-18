<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Services\EntityPersister;
use App\Tests\AbstractBaseFunctionalTest;
use Doctrine\ORM\EntityManagerInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackEntity;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\EntityInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Job;
use webignition\BasilWorker\PersistenceBundle\Entity\Source;

class EntityPersisterTest extends AbstractBaseFunctionalTest
{
    private EntityPersister $entityPersister;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $entityPersister = self::$container->get(EntityPersister::class);
        \assert($entityPersister instanceof EntityPersister);
        $this->entityPersister = $entityPersister;

        $entityManager = self::$container->get(EntityManagerInterface::class);
        \assert($entityManager instanceof EntityManagerInterface);
        $this->entityManager = $entityManager;
    }

    /**
     * @dataProvider persistDataProvider
     */
    public function testPersist(EntityInterface $entity): void
    {
        $repository = $this->entityManager->getRepository($entity::class);
        self::assertCount(0, $repository->findAll());

        $this->entityPersister->persist($entity);
        self::assertCount(1, $repository->findAll());
    }

    /**
     * @return array[]
     */
    public function persistDataProvider(): array
    {
        return [
            'callback' => [
                'entity' => CallbackEntity::create(CallbackInterface::TYPE_COMPILATION_FAILED, []),
            ],
            'job' => [
                'entity' => Job::create('label content', 'http://example.com/callback', 600),
            ],
            'source' => [
                'entity' => Source::create(Source::TYPE_TEST, 'Test/test.yml'),
            ],
        ];
    }
}
