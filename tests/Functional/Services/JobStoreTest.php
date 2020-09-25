<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Job;
use App\Services\JobStore;
use App\Tests\Functional\AbstractBaseFunctionalTest;

class JobStoreTest extends AbstractBaseFunctionalTest
{
    private JobStore $jobStore;

    protected function setUp(): void
    {
        parent::setUp();

        $jobRetriever = self::$container->get(JobStore::class);
        self::assertInstanceOf(JobStore::class, $jobRetriever);

        if ($jobRetriever instanceof JobStore) {
            $this->jobStore = $jobRetriever;
        }
    }

    public function testRetrieveReturnsNull()
    {
        self::assertNull($this->jobStore->retrieve());
    }

    public function testStore()
    {
        $label = md5('label source');
        $callbackUrl = 'http://example.com/callback';

        $job = Job::create($label, $callbackUrl);
        $this->jobStore->store($job);

        self::assertEquals($job, $this->jobStore->retrieve());
    }
}
