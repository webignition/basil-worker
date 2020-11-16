<?php

declare(strict_types=1);

namespace App\Tests\Functional\Repository;

use App\Entity\Callback\CallbackEntity;
use App\Model\Callback\CallbackModelInterface;
use App\Repository\CallbackRepository;
use App\Services\CallbackStore;
use App\Tests\AbstractBaseFunctionalTest;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class CallbackRepositoryTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private CallbackRepository $repository;
    private CallbackStore $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    public function testFind()
    {
        self::assertNull($this->repository->find(0));

        $callback = CallbackEntity::create(CallbackModelInterface::TYPE_COMPILE_FAILURE, []);
        $this->store->store($callback);

        $retrievedCallback = $this->repository->find($callback->getId());
        self::assertEquals($callback, $retrievedCallback);
    }
}
