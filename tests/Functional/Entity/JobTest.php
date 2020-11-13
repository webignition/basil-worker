<?php

declare(strict_types=1);

namespace App\Tests\Functional\Entity;

use App\Entity\Job;
use App\Services\JobStore;
use App\Tests\TestClassServicePropertyInjectorTrait;

class JobTest extends AbstractEntityTest
{
    use TestClassServicePropertyInjectorTrait;

    private JobStore $jobStore;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    /**
     * @dataProvider hydratedJobReturnsSourcesAsStringArrayDataProvider
     *
     * @param string[] $sources
     */
    public function testHydratedJobReturnsSourcesAsStringArray(array $sources)
    {
        $job = $this->jobStore->create(md5('label source'), 'http://example.com/callback');
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
