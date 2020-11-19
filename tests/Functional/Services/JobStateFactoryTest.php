<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Callback\CompileFailureCallback;
use App\Entity\Test;
use App\Model\JobState;
use App\Services\CallbackStore;
use App\Services\JobStateFactory;
use App\Services\JobStore;
use App\Services\TestFactory;
use App\Services\TestStore;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\MockSuiteManifest;
use webignition\BasilCompilerModels\ErrorOutputInterface;
use webignition\BasilCompilerModels\TestManifest;
use webignition\BasilModels\Test\Configuration;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class JobStateFactoryTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private JobStateFactory $jobStateFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(callable $setup, JobState $expectedState)
    {
        $setup([
            JobStore::class => self::$container->get(JobStore::class),
            CallbackStore::class => self::$container->get(CallbackStore::class),
            TestFactory::class => self::$container->get(TestFactory::class),
            TestStore::class => self::$container->get(TestStore::class),
        ]);

        self::assertEquals($expectedState, $this->jobStateFactory->create());
    }

    public function createDataProvider(): array
    {
        return [
            'compilation-awaiting: no job' => [
                'setup' => function () {
                },
                'expectedState' => new JobState(JobState::STATE_COMPILATION_AWAITING),
            ],
            'compilation-awaiting: has job, no sources' => [
                'setup' => function (array $services) {
                    $jobStore = $services[JobStore::class];
                    if ($jobStore instanceof JobStore) {
                        $jobStore->create('', '');
                    }
                },
                'expectedState' => new JobState(JobState::STATE_COMPILATION_AWAITING),
            ],
            'compilation-running: has job, has sources, no sources compiled' => [
                'setup' => function (array $services) {
                    $jobStore = $services[JobStore::class];
                    if ($jobStore instanceof JobStore) {
                        $job = $jobStore->create('', '');
                        $job->setSources([
                            'Test/test1.yml',
                            'Test/test2.yml',
                        ]);
                        $jobStore->store($job);
                    }
                },
                'expectedState' => new JobState(JobState::STATE_COMPILATION_RUNNING),
            ],
            'compilation-failed: has job, has sources, has more than zero compile-failure callbacks' => [
                'setup' => function (array $services) {
                    $jobStore = $services[JobStore::class];
                    if ($jobStore instanceof JobStore) {
                        $job = $jobStore->create('', '');
                        $job->setSources([
                            'Test/test1.yml',
                            'Test/test2.yml',
                        ]);
                        $jobStore->store($job);
                    }

                    $errorOutput = \Mockery::mock(ErrorOutputInterface::class);
                    $errorOutput
                        ->shouldReceive('getData')
                        ->andReturn([]);

                    $compileFailureCallback = new CompileFailureCallback($errorOutput);

                    $callbackStore = $services[CallbackStore::class];
                    if ($callbackStore instanceof CallbackStore) {
                        $callbackStore->store($compileFailureCallback);
                    }
                },
                'expectedState' => new JobState(JobState::STATE_COMPILATION_FAILED),
            ],
            'execution-awaiting: compilation workflow complete and execution workflow not started' => [
                'setup' => function (array $services) {
                    $jobStore = $services[JobStore::class];
                    if ($jobStore instanceof JobStore) {
                        $job = $jobStore->create('', '');
                        $job->setSources([
                            'Test/test1.yml',
                        ]);
                        $jobStore->store($job);
                    }

                    $testManifests = [
                        new TestManifest(
                            new Configuration('chrome', 'http://example.com'),
                            '/app/source/Test/test1.yml',
                            '/app/tests/GeneratedTest1.php',
                            1
                        ),
                    ];

                    $manifest = (new MockSuiteManifest())
                        ->withGetTestManifestsCall($testManifests)
                        ->getMock();

                    $testFactory = $services[TestFactory::class];
                    if ($testFactory instanceof TestFactory) {
                        $testFactory->createFromManifestCollection($manifest->getTestManifests());
                    }
                },
                'expectedState' => new JobState(JobState::STATE_EXECUTION_AWAITING),
            ],
            'execution-running' => [
                'setup' => function (array $services) {
                    $jobStore = $services[JobStore::class];
                    if ($jobStore instanceof JobStore) {
                        $job = $jobStore->create('', '');
                        $job->setSources([
                            'Test/test1.yml',
                            'Test/test2.yml',
                        ]);
                        $jobStore->store($job);
                    }

                    $testManifests = [
                        new TestManifest(
                            new Configuration('chrome', 'http://example.com'),
                            '/app/source/Test/test1.yml',
                            '/app/tests/GeneratedTest1.php',
                            1
                        ),
                        new TestManifest(
                            new Configuration('chrome', 'http://example.com'),
                            '/app/source/Test/test2.yml',
                            '/app/tests/GeneratedTest2.php',
                            1
                        ),
                    ];

                    $manifest = (new MockSuiteManifest())
                        ->withGetTestManifestsCall($testManifests)
                        ->getMock();

                    $testFactory = $services[TestFactory::class];
                    if ($testFactory instanceof TestFactory) {
                        $tests = $testFactory->createFromManifestCollection($manifest->getTestManifests());

                        $test = $tests[0];
                        $test->setState(Test::STATE_COMPLETE);

                        $testStore = $services[TestStore::class];
                        if ($testStore instanceof TestStore) {
                            $testStore->store($test);
                        }
                    }
                },
                'expectedState' => new JobState(JobState::STATE_EXECUTION_RUNNING),
            ],
            'execution-complete' => [
                'setup' => function (array $services) {
                    $jobStore = $services[JobStore::class];
                    if ($jobStore instanceof JobStore) {
                        $job = $jobStore->create('', '');
                        $job->setSources([
                            'Test/test1.yml',
                        ]);
                        $jobStore->store($job);
                    }

                    $testManifests = [
                        new TestManifest(
                            new Configuration('chrome', 'http://example.com'),
                            '/app/source/Test/test1.yml',
                            '/app/tests/GeneratedTest1.php',
                            1
                        ),
                    ];

                    $manifest = (new MockSuiteManifest())
                        ->withGetTestManifestsCall($testManifests)
                        ->getMock();

                    $testFactory = $services[TestFactory::class];
                    if ($testFactory instanceof TestFactory) {
                        $tests = $testFactory->createFromManifestCollection($manifest->getTestManifests());

                        $test = $tests[0];
                        $test->setState(Test::STATE_COMPLETE);

                        $testStore = $services[TestStore::class];
                        if ($testStore instanceof TestStore) {
                            $testStore->store($test);
                        }
                    }
                },
                'expectedState' => new JobState(JobState::STATE_EXECUTION_COMPLETE),
            ],
            'execution-cancelled: test.state == failed > 0' => [
                'setup' => function (array $services) {
                    $jobStore = $services[JobStore::class];
                    if ($jobStore instanceof JobStore) {
                        $job = $jobStore->create('', '');
                        $job->setSources([
                            'Test/test1.yml',
                        ]);
                        $jobStore->store($job);
                    }

                    $testManifests = [
                        new TestManifest(
                            new Configuration('chrome', 'http://example.com'),
                            '/app/source/Test/test1.yml',
                            '/app/tests/GeneratedTest1.php',
                            1
                        ),
                    ];

                    $manifest = (new MockSuiteManifest())
                        ->withGetTestManifestsCall($testManifests)
                        ->getMock();

                    $testFactory = $services[TestFactory::class];
                    if ($testFactory instanceof TestFactory) {
                        $tests = $testFactory->createFromManifestCollection($manifest->getTestManifests());

                        $test = $tests[0];
                        $test->setState(Test::STATE_FAILED);

                        $testStore = $services[TestStore::class];
                        if ($testStore instanceof TestStore) {
                            $testStore->store($test);
                        }
                    }
                },
                'expectedState' => new JobState(JobState::STATE_EXECUTION_CANCELLED),
            ],
            'execution-cancelled: test.state == cancelled > 0' => [
                'setup' => function (array $services) {
                    $jobStore = $services[JobStore::class];
                    if ($jobStore instanceof JobStore) {
                        $job = $jobStore->create('', '');
                        $job->setSources([
                            'Test/test1.yml',
                        ]);
                        $jobStore->store($job);
                    }

                    $testManifests = [
                        new TestManifest(
                            new Configuration('chrome', 'http://example.com'),
                            '/app/source/Test/test1.yml',
                            '/app/tests/GeneratedTest1.php',
                            1
                        ),
                    ];

                    $manifest = (new MockSuiteManifest())
                        ->withGetTestManifestsCall($testManifests)
                        ->getMock();

                    $testFactory = $services[TestFactory::class];
                    if ($testFactory instanceof TestFactory) {
                        $tests = $testFactory->createFromManifestCollection($manifest->getTestManifests());

                        $test = $tests[0];
                        $test->setState(Test::STATE_CANCELLED);

                        $testStore = $services[TestStore::class];
                        if ($testStore instanceof TestStore) {
                            $testStore->store($test);
                        }
                    }
                },
                'expectedState' => new JobState(JobState::STATE_EXECUTION_CANCELLED),
            ],
        ];
    }
}
