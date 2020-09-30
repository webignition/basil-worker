<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Job;
use App\Services\JobStore;
use App\Tests\Functional\AbstractBaseFunctionalTest;
use Doctrine\ORM\EntityManagerInterface;

class JobStoreTest extends AbstractBaseFunctionalTest
{
    private JobStore $store;

    protected function setUp(): void
    {
        parent::setUp();

        $store = self::$container->get(JobStore::class);
        self::assertInstanceOf(JobStore::class, $store);

        if ($store instanceof JobStore) {
            $this->store = $store;
        }

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
        self::assertSame(Job::STATE_COMPILATION_AWAITING, $job->getState());

        $job->setState(Job::STATE_COMPILATION_RUNNING);
        $this->store->store();

        $entityManager = self::$container->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        $entityManager->clear();

        $newStore = new JobStore($entityManager);

        $retrievedJob = $newStore->getJob();

        self::assertNotSame($job, $retrievedJob);
        self::assertEquals($job, $retrievedJob);
    }
}
