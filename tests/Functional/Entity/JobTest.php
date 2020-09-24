<?php

namespace App\Tests\Functional\Entity;

use App\Entity\Job;
use App\Entity\JobState;
use Doctrine\ORM\EntityManagerInterface;

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

        $hasPersisted = $this->persistJob($job);
        self::assertTrue($hasPersisted);
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

        $hasPersisted = $this->persistJob($job);
        self::assertTrue($hasPersisted);

        $retrievedJob = null;
        if ($this->entityManager instanceof EntityManagerInterface) {
            $this->entityManager->clear(Job::class);
            $this->entityManager->close();
            $retrievedJob = $this->entityManager->find(Job::class, Job::ID);
        }

        self::assertInstanceOf(Job::class, $retrievedJob);
        self::assertSame($sources, $retrievedJob->getSources());
    }

    private function createJobState(): JobState
    {
        $state = JobState::create('job-state-name');
        if ($this->entityManager instanceof EntityManagerInterface) {
            $this->entityManager->persist($state);
            $this->entityManager->flush();
        }
        self::assertNotNull($state->getId());

        return $state;
    }

    private function persistJob(Job $job): bool
    {
        $hasPersisted = false;
        if ($this->entityManager instanceof EntityManagerInterface) {
            self::assertFalse($this->entityManager->contains($job));
            $this->entityManager->persist($job);
            $this->entityManager->flush();
            $hasPersisted = true;
        }

        return $hasPersisted;
    }
}
