<?php

declare(strict_types=1);

namespace App\Tests\Functional\Entity;

use App\Entity\Job;
use App\Services\JobStore;

class JobTest extends AbstractEntityTest
{
    private JobStore $jobStore;

    protected function setUp(): void
    {
        parent::setUp();

        $jobStore = self::$container->get(JobStore::class);
        if ($jobStore instanceof JobStore) {
            $this->jobStore = $jobStore;
        }
    }

    public function testCreate()
    {
        $label = md5('label source');
        $callbackUrl = 'http://example.com/callback';

        $job = Job::create($label, $callbackUrl);

        self::assertSame(1, $job->getId());
        self::assertSame(Job::STATE_COMPILATION_AWAITING, $job->getState());
        self::assertSame($label, $job->getLabel());
        self::assertSame($callbackUrl, $job->getCallbackUrl());
        self::assertSame([], $job->getSources());

        $this->jobStore->store($job);
    }

    /**
     * @dataProvider hydratedJobReturnsSourcesAsStringArrayDataProvider
     *
     * @param string[] $sources
     */
    public function testHydratedJobReturnsSourcesAsStringArray(array $sources)
    {
        $job = Job::create(md5('label source'), 'http://example.com/callback');
        $job->setSources($sources);

        $this->jobStore->store($job);

        $this->entityManager->clear(Job::class);
        $this->entityManager->close();
        $retrievedJob = $this->entityManager->find(Job::class, Job::ID);

        self::assertInstanceOf(Job::class, $retrievedJob);
        self::assertSame($sources, $retrievedJob->getSources());
    }

    public function hydratedJobReturnsSourcesAsStringArrayDataProvider(): array
    {
        return [
            'empty' => [
                'sources' => [],
            ],
            'non-empty' => [
                'sources' => [
                    '/app/basil/test1.yml',
                    '/app/basil/test2.yml',
                    '/app/basil/test3.yml',
                ],
            ],
        ];
    }
}
