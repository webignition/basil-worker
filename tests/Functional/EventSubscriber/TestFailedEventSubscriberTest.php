<?php

declare(strict_types=1);

namespace App\Tests\Functional\EventSubscriber;

use App\Entity\Job;
use App\Entity\Test;
use App\Entity\TestConfiguration;
use App\Event\TestFailedEvent;
use App\EventSubscriber\TestFailedEventSubscriber;
use App\Repository\TestRepository;
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

        $testRepository = self::$container->get(TestRepository::class);
        self::assertInstanceOf(TestRepository::class, $testRepository);
        if ($testRepository instanceof TestRepository) {
            $this->testRepository = $testRepository;
        }
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
