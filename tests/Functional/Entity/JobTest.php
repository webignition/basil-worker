<?php

declare(strict_types=1);

namespace App\Tests\Functional\Entity;

use App\Entity\Job;
use App\Tests\Services\InvokableFactory\JobSetup;
use App\Tests\Services\InvokableFactory\JobSetupInvokableFactory;
use App\Tests\Services\InvokableHandler;
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
        $this->invokableHandler->invoke(JobSetupInvokableFactory::setup(
            (new JobSetup())
                ->withSources($sources)
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
