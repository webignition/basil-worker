<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Job;
use App\Entity\JobState;
use App\Services\JobRetriever;
use App\Tests\Functional\AbstractBaseFunctionalTest;
use Doctrine\ORM\EntityManagerInterface;

class JobRetrieverTest extends AbstractBaseFunctionalTest
{
    private JobRetriever $jobRetriever;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $jobRetriever = self::$container->get(JobRetriever::class);
        self::assertInstanceOf(JobRetriever::class, $jobRetriever);

        if ($jobRetriever instanceof JobRetriever) {
            $this->jobRetriever = $jobRetriever;
        }

        $entityManager = self::$container->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        if ($entityManager instanceof EntityManagerInterface) {
            $this->entityManager = $entityManager;
        }
    }

    public function testRetrieveReturnsNull()
    {
        self::assertNull($this->jobRetriever->retrieve());
    }

    public function testRetrieveReturnsJob()
    {
        $entityManager = self::$container->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        $state = JobState::create('job-state-name');
        $this->entityManager->persist($state);
        $this->entityManager->flush();

        $label = md5('label source');
        $callbackUrl = 'http://example.com/callback';
        $sources = [
            '/app/basil/test1.yml',
            '/app/basil/test2.yml',
            '/app/basil/test3.yml',
        ];

        $job = Job::create($state, $label, $callbackUrl, $sources);
        $this->entityManager->persist($job);
        $this->entityManager->flush();

        self::assertEquals($job, $this->jobRetriever->retrieve());
    }
}