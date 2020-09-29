<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Job;
use App\Services\JobStore;
use App\Tests\Functional\AbstractBaseFunctionalTest;

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
    }

    public function testRetrieveReturnsNull()
    {
        self::assertNull($this->store->retrieve());
    }

    public function testStore()
    {
        $label = md5('label source');
        $callbackUrl = 'http://example.com/callback';

        $job = Job::create($label, $callbackUrl);
        $this->store->store($job);

        self::assertEquals($job, $this->store->retrieve());
    }
}
