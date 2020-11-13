<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\CallbackEntity;
use App\Services\CallbackStore;
use App\Tests\AbstractBaseFunctionalTest;
use Doctrine\ORM\EntityManagerInterface;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class CallbackStoreTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private CallbackStore $callbackStore;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    public function testStore()
    {
        $callbackRepository = $this->entityManager->getRepository(CallbackEntity::class);

        self::assertCount(0, $callbackRepository->findAll());

        $type = CallbackEntity::TYPE_COMPILE_FAILURE;
        $payload = [
            'key1' => 'value1',
            'key2' => 'value2',
        ];

        $callback = CallbackEntity::create($type, $payload);
        self::assertNull($callback->getId());

        $this->callbackStore->store($callback);
        self::assertNotNull($callback->getId());

        self::assertSame(CallbackEntity::STATE_AWAITING, $callback->getState());

        $callback->setState(CallbackEntity::STATE_QUEUED);
        $this->callbackStore->store($callback);

        $retrievedCallback = $callbackRepository->find($callback->getId());
        self::assertEquals($callback, $retrievedCallback);
    }
}
