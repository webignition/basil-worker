<?php

declare(strict_types=1);

namespace App\Tests\Functional\EventSubscriber;

use App\Entity\Job;
use App\Entity\Test;
use App\Entity\TestConfiguration;
use App\Event\TestFailedEvent;
use App\EventSubscriber\TestFailedEventSubscriber;
use App\Repository\TestRepository;
use App\Services\JobStateMutator;
use App\Services\JobStore;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\TestTestFactory;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class TestFailedEventSubscriberTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;

    private TestFailedEventSubscriber $eventSubscriber;
    private TestTestFactory $testFactory;
    private JobStateMutator $jobStateMutator;
    private Job $job;
    private TestRepository $testRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $eventSubscriber = self::$container->get(TestFailedEventSubscriber::class);
        self::assertInstanceOf(TestFailedEventSubscriber::class, $eventSubscriber);
        if ($eventSubscriber instanceof TestFailedEventSubscriber) {
            $this->eventSubscriber = $eventSubscriber;
        }

        $jobStore = self::$container->get(JobStore::class);
        self::assertInstanceOf(JobStore::class, $jobStore);
        if ($jobStore instanceof JobStore) {
            $this->job = $jobStore->create('label content', 'http://example.com/callback');
        }

        $testFactory = self::$container->get(TestTestFactory::class);
        self::assertInstanceOf(TestTestFactory::class, $testFactory);
        if ($testFactory instanceof TestTestFactory) {
            $this->testFactory = $testFactory;
        }

        $jobStateMutator = self::$container->get(JobStateMutator::class);
        self::assertInstanceOf(JobStateMutator::class, $jobStateMutator);
        if ($jobStateMutator instanceof JobStateMutator) {
            $this->jobStateMutator = $jobStateMutator;
        }

        $testRepository = self::$container->get(TestRepository::class);
        self::assertInstanceOf(TestRepository::class, $testRepository);
        if ($testRepository instanceof TestRepository) {
            $this->testRepository = $testRepository;
        }
    }

    public function testSetTestStateToFailed()
    {
        $test = $this->createTest();
        self::assertNotSame(Test::STATE_FAILED, $test->getState());

        $this->eventSubscriber->setTestStateToFailed(new TestFailedEvent($test));
        self::assertSame(Test::STATE_FAILED, $test->getState());
    }

    /**
     * @dataProvider setJobStateToCancelledDataProvider
     */
    public function testSetJobStateToCancelled(callable $jobMutator, string $expectedJobState)
    {
        $jobMutator($this->jobStateMutator, $this->job);

        $this->eventSubscriber->setJobStateToCancelled();

        self::assertSame($expectedJobState, $this->job->getState());
    }

    public function setJobStateToCancelledDataProvider(): array
    {
        return [
            'job state: compilation awaiting' => [
                'jobMutator' => function () {
                },
                'expectedJobState' => Job::STATE_EXECUTION_CANCELLED,
            ],
            'job state: compilation running' => [
                'jobMutator' => function (JobStateMutator $jobStateMutator, Job $job) {
                    $jobStateMutator->setCompilationRunning();
                    self::assertSame(Job::STATE_COMPILATION_RUNNING, $job->getState());
                },
                'expectedJobState' => Job::STATE_EXECUTION_CANCELLED,
            ],
            'job state: compilation failed' => [
                'jobMutator' => function (JobStateMutator $jobStateMutator, Job $job) {
                    $jobStateMutator->setCompilationFailed();
                    self::assertSame(Job::STATE_COMPILATION_FAILED, $job->getState());
                },
                'expectedJobState' => Job::STATE_COMPILATION_FAILED,
            ],
            'job state: execution awaiting' => [
                'jobMutator' => function (JobStateMutator $jobStateMutator, Job $job) {
                    $jobStateMutator->setExecutionAwaiting();
                    self::assertSame(Job::STATE_EXECUTION_AWAITING, $job->getState());
                },
                'expectedJobState' => Job::STATE_EXECUTION_CANCELLED,
            ],
            'job state: execution running' => [
                'jobMutator' => function (JobStateMutator $jobStateMutator, Job $job) {
                    $jobStateMutator->setExecutionRunning();
                    self::assertSame(Job::STATE_EXECUTION_RUNNING, $job->getState());
                },
                'expectedJobState' => Job::STATE_EXECUTION_CANCELLED,
            ],
            'job state: execution complete' => [
                'jobMutator' => function (JobStateMutator $jobStateMutator, Job $job) {
                    $jobStateMutator->setExecutionComplete();
                    self::assertSame(Job::STATE_EXECUTION_COMPLETE, $job->getState());
                },
                'expectedJobState' => Job::STATE_EXECUTION_COMPLETE,
            ],
            'job state: execution cancelled' => [
                'jobMutator' => function (JobStateMutator $jobStateMutator, Job $job) {
                    $jobStateMutator->setExecutionCancelled();
                    self::assertSame(Job::STATE_EXECUTION_CANCELLED, $job->getState());
                },
                'expectedJobState' => Job::STATE_EXECUTION_CANCELLED,
            ],
        ];
    }

    /**
     * @dataProvider cancelAwaitingTestsDataProvider
     *
     * @param callable $setup
     * @param array<Test::STATE_*> $expectedTestStates
     */
    public function testCancelAwaitingTests(callable $setup, array $expectedTestStates)
    {
        $setup($this->testFactory);

        $this->eventSubscriber->cancelAwaitingTests();

        $this->assertTestStates($this->testRepository->findAll(), $expectedTestStates);
    }

    public function cancelAwaitingTestsDataProvider(): array
    {
        return [
            'no tests' => [
                'setup' => function () {
                },
                'expectedTestStates' => [],
            ],
            'no awaiting tests' => [
                'setup' => function (TestTestFactory $testFactory) {
                    $configuration = TestConfiguration::create('chrome', 'http://example.com');

                    $testFactory->create($configuration, '', '', 1, Test::STATE_RUNNING);
                    $testFactory->create($configuration, '', '', 1, Test::STATE_FAILED);
                    $testFactory->create($configuration, '', '', 1, Test::STATE_COMPLETE);
                    $testFactory->create($configuration, '', '', 1, Test::STATE_CANCELLED);
                },
                'expectedTestStates' => [
                    Test::STATE_RUNNING,
                    Test::STATE_FAILED,
                    Test::STATE_COMPLETE,
                    Test::STATE_CANCELLED,
                ],
            ],
            'all awaiting tests' => [
                'setup' => function (TestTestFactory $testFactory) {
                    $configuration = TestConfiguration::create('chrome', 'http://example.com');

                    $testFactory->create($configuration, '', '', 1, Test::STATE_AWAITING);
                    $testFactory->create($configuration, '', '', 1, Test::STATE_AWAITING);
                },
                'expectedTestStates' => [
                    Test::STATE_CANCELLED,
                    Test::STATE_CANCELLED,
                ],
            ],
            'mixed states' => [
                'setup' => function (TestTestFactory $testFactory) {
                    $configuration = TestConfiguration::create('chrome', 'http://example.com');

                    $testFactory->create($configuration, '', '', 1, Test::STATE_RUNNING);
                    $testFactory->create($configuration, '', '', 1, Test::STATE_AWAITING);
                    $testFactory->create($configuration, '', '', 1, Test::STATE_FAILED);
                    $testFactory->create($configuration, '', '', 1, Test::STATE_AWAITING);
                    $testFactory->create($configuration, '', '', 1, Test::STATE_COMPLETE);
                    $testFactory->create($configuration, '', '', 1, Test::STATE_AWAITING);
                    $testFactory->create($configuration, '', '', 1, Test::STATE_CANCELLED);
                },
                'expectedTestStates' => [
                    Test::STATE_RUNNING,
                    Test::STATE_CANCELLED,
                    Test::STATE_FAILED,
                    Test::STATE_CANCELLED,
                    Test::STATE_COMPLETE,
                    Test::STATE_CANCELLED,
                    Test::STATE_CANCELLED,
                ],
            ],
        ];
    }

    public function testIntegration()
    {
        self::assertSame(Job::STATE_COMPILATION_AWAITING, $this->job->getState());

        $configuration = TestConfiguration::create('chrome', 'http://example.com');
        $this->testFactory->create($configuration, '', '', 1, Test::STATE_AWAITING);
        $this->testFactory->create($configuration, '', '', 1, Test::STATE_AWAITING);

        $test = $this->createTest();
        self::assertSame(Test::STATE_AWAITING, $test->getState());

        $eventDispatcher = self::$container->get(EventDispatcherInterface::class);
        if ($eventDispatcher instanceof EventDispatcherInterface) {
            $eventDispatcher->dispatch(new TestFailedEvent($test));
        }

        self::assertSame(Job::STATE_EXECUTION_CANCELLED, $this->job->getState());
        self::assertSame(Test::STATE_FAILED, $test->getState());

        $this->assertTestStates(
            $this->testRepository->findAll(),
            [
                Test::STATE_CANCELLED,
                Test::STATE_CANCELLED,
                Test::STATE_FAILED,
            ]
        );
    }

    /**
     * @param Test[] $tests
     * @param array<Test::STATE_*> $expectedTestStates
     */
    private function assertTestStates(array $tests, array $expectedTestStates): void
    {
        self::assertCount(count($expectedTestStates), $tests);

        foreach ($tests as $testIndex => $test) {
            $expectedTestState = $expectedTestStates[$testIndex] ?? null;

            self::assertSame($expectedTestState, $test->getState());
        }
    }

    private function createTest(): Test
    {
        return $this->testFactory->create(
            TestConfiguration::create('chrome', 'http://example.com/'),
            '/app/source/Test/test.yml',
            '/generated/GeneratedTest.php',
            1
        );
    }
}
