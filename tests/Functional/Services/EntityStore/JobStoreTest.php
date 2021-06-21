<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\EntityStore;

use App\Services\EntityPersister;
use App\Services\EntityStore\JobStore;
use App\Tests\AbstractBaseFunctionalTest;
use App\Entity\Job;

class JobStoreTest extends AbstractBaseFunctionalTest
{
    private JobStore $store;
    private EntityPersister $persister;

    protected function setUp(): void
    {
        parent::setUp();

        $store = self::$container->get(JobStore::class);
        self::assertInstanceOf(JobStore::class, $store);
        if ($store instanceof JobStore) {
            $this->store = $store;
        }

        $persister = self::$container->get(EntityPersister::class);
        self::assertInstanceOf(EntityPersister::class, $persister);
        if ($persister instanceof EntityPersister) {
            $this->persister = $persister;
        }
    }

    public function testHas(): void
    {
        self::assertFalse($this->store->has());

        $this->persister->persist(Job::create('label content', 'http://example.com/callback', 600));
        self::assertTrue($this->store->has());
    }

    public function testGet(): void
    {
        $job = Job::create('label content', 'http://example.com/callback', 600);
        $this->persister->persist($job);

        self::assertSame($this->store->get(), $job);
    }
}
