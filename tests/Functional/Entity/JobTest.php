<?php

declare(strict_types=1);

namespace App\Tests\Functional\Entity;

use App\Entity\Job;
use App\Services\JobStore;
use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\ServiceReference;
use App\Tests\Services\InvokableHandler;
use Doctrine\ORM\EntityManagerInterface;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class JobTest extends AbstractEntityTest
{
    use TestClassServicePropertyInjectorTrait;

    private InvokableHandler $invokableHandler;

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
        $this->invokableHandler->invoke(new Invokable(
            function (JobStore $jobStore, EntityManagerInterface $entityManager, array $sources) {
                $job = $jobStore->create('label', 'http://example.com', 600);

                $job->setSources($sources);

                $entityManager->persist($job);
                $entityManager->flush();
            },
            [
                new ServiceReference(JobStore::class),
                new ServiceReference(EntityManagerInterface::class),
                $sources
            ]
        ));

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
