<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Services\JobStore;
use App\Tests\AbstractBaseFunctionalTest;
use Doctrine\ORM\EntityManagerInterface;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class JobStoreTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private JobStore $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();

        self::assertFalse($this->store->hasJob());
    }

    public function testCreate()
    {
        $label = md5('label source');
        $callbackUrl = 'http://example.com/callback';

        $job = $this->store->create($label, $callbackUrl);

        self::assertTrue($this->store->hasJob());
        self::assertSame($job, $this->store->getJob());
    }

    public function testStore()
    {
        $label = md5('label source');
        $callbackUrl = 'http://example.com/callback';

        $job = $this->store->create($label, $callbackUrl);
        $this->store->store($job);

        $entityManager = self::$container->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        $entityManager->clear();

        $newStore = new JobStore($entityManager);

        $retrievedJob = $newStore->getJob();

        self::assertNotSame($job, $retrievedJob);
        self::assertEquals($job, $retrievedJob);
    }
}
