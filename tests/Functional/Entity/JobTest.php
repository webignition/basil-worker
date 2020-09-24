<?php

namespace App\Tests\Functional\Entity;

use App\Entity\Job;
use App\Entity\JobState;

class JobTest extends AbstractEntityTest
{
    public function testCreate()
    {
        $state = $this->createJobState();

        $label = md5('label source');
        $callbackUrl = 'http://example.com/callback';
        $sources = [
            '/app/basil/test1.yml',
            '/app/basil/test2.yml',
            '/app/basil/test3.yml',
        ];

        $job = Job::create($state, $label, $callbackUrl, $sources);

        self::assertSame(1, $job->getId());
        self::assertSame($state, $job->getState());
        self::assertSame($label, $job->getLabel());
        self::assertSame($callbackUrl, $job->getCallbackUrl());
        self::assertSame($sources, $job->getSources());

        $this->persistJob($job);
    }

    public function testHydratedJobReturnsSourcesAsStringArray()
    {
        $state = $this->createJobState();
        $sources = [
            '/app/basil/test1.yml',
            '/app/basil/test2.yml',
            '/app/basil/test3.yml',
        ];

        $job = Job::create(
            $state,
            md5('label source'),
            'http://example.com/callback',
            $sources
        );

        $this->persistJob($job);

        $this->entityManager->clear(Job::class);
        $this->entityManager->close();
        $retrievedJob = $this->entityManager->find(Job::class, Job::ID);

        self::assertInstanceOf(Job::class, $retrievedJob);
        self::assertSame($sources, $retrievedJob->getSources());
    }

    private function createJobState(): JobState
    {
        $state = JobState::create('job-state-name');
        $this->entityManager->persist($state);
        $this->entityManager->flush();

        self::assertNotNull($state->getId());

        return $state;
    }

    private function persistJob(Job $job): void
    {
        self::assertFalse($this->entityManager->contains($job));
        $this->entityManager->persist($job);
        $this->entityManager->flush();
        self::assertTrue($this->entityManager->contains($job));
    }
}
