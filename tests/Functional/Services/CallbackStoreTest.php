<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\CallbackEntity;
use App\Entity\CallbackEntityInterface;
use App\Entity\ExecuteDocumentReceivedCallback;
use App\Services\CallbackStore;
use App\Tests\AbstractBaseFunctionalTest;
use Doctrine\ORM\EntityManagerInterface;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;
use webignition\YamlDocument\Document;

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

        $callback = ExecuteDocumentReceivedCallback::create(new Document('[]'));

        self::assertNull($callback->getId());

        $this->callbackStore->store($callback);
        self::assertNotNull($callback->getId());

        self::assertSame(CallbackEntityInterface::STATE_AWAITING, $callback->getState());

        $callback->setState(CallbackEntityInterface::STATE_QUEUED);
        $this->callbackStore->store($callback);

        $retrievedCallback = $callbackRepository->find($callback->getId());
        self::assertEquals($callback, $retrievedCallback);
    }
}
