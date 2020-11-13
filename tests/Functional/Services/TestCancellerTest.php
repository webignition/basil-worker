<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Test;
use App\Entity\TestConfiguration;
use App\Event\TestFailedEvent;
use App\Services\TestCanceller;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\TestTestFactory;
use App\Tests\Services\TestTestRepository;
use Psr\EventDispatcher\EventDispatcherInterface;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class TestCancellerTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private TestCanceller $testCanceller;
    private TestTestFactory $testFactory;
    private EventDispatcherInterface $eventDispatcher;
    private TestTestRepository $testTestRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    /**
     * @dataProvider cancelAwaitingDataProvider
     *
     * @param callable $setup
     * @param array<Test::STATE_*> $expectedInitialStates
     * @param array<Test::STATE_*> $expectedStates
     */
    public function testCancelAwaiting(callable $setup, array $expectedInitialStates, array $expectedStates)
    {
        $setup($this->testFactory);
        self::assertSame($this->testTestRepository->getStates(), $expectedInitialStates);

        $this->testCanceller->cancelAwaiting();

        self::assertSame($this->testTestRepository->getStates(), $expectedStates);
    }

    public function cancelAwaitingDataProvider(): array
    {
        return [
            'no tests' => [
                'setup' => function () {
                    return [];
                },
                'expectedInitialStates' => [],
                'expectedStates' => [],
            ],
            'no awaiting tests' => [
                'setup' => function (TestTestFactory $testFactory) {
                    return $this->createTestsWithStates($testFactory, [
                        Test::STATE_COMPLETE,
                        Test::STATE_CANCELLED,
                        Test::STATE_FAILED,
                        Test::STATE_RUNNING,
                    ]);
                },
                'expectedInitialStates' => [
                    Test::STATE_COMPLETE,
                    Test::STATE_CANCELLED,
                    Test::STATE_FAILED,
                    Test::STATE_RUNNING,
                ],
                'expectedStates' => [
                    Test::STATE_COMPLETE,
                    Test::STATE_CANCELLED,
                    Test::STATE_FAILED,
                    Test::STATE_RUNNING,
                ],
            ],
            'all awaiting tests' => [
                'setup' => function (TestTestFactory $testFactory) {
                    return $this->createTestsWithStates($testFactory, [
                        Test::STATE_AWAITING,
                        Test::STATE_AWAITING,
                    ]);
                },
                'expectedInitialStates' => [
                    Test::STATE_AWAITING,
                    Test::STATE_AWAITING,
                ],
                'expectedStates' => [
                    Test::STATE_CANCELLED,
                    Test::STATE_CANCELLED,
                ],
            ],
            'mixed' => [
                'setup' => function (TestTestFactory $testFactory) {
                    return $this->createTestsWithStates($testFactory, [
                        Test::STATE_COMPLETE,
                        Test::STATE_CANCELLED,
                        Test::STATE_FAILED,
                        Test::STATE_RUNNING,
                        Test::STATE_AWAITING,
                    ]);
                },
                'expectedInitialStates' => [
                    Test::STATE_COMPLETE,
                    Test::STATE_CANCELLED,
                    Test::STATE_FAILED,
                    Test::STATE_RUNNING,
                    Test::STATE_AWAITING,
                ],
                'expectedStates' => [
                    Test::STATE_COMPLETE,
                    Test::STATE_CANCELLED,
                    Test::STATE_FAILED,
                    Test::STATE_RUNNING,
                    Test::STATE_CANCELLED,
                ],
            ],
        ];
    }

    /**
     * @dataProvider cancelAwaitingFromTestFailedEventDataProvider
     *
     * @param callable $setup
     * @param array<Test::STATE_*> $expectedInitialStates
     * @param array<Test::STATE_*> $expectedStates
     */
    public function testCancelAwaitingFromTestFailedEvent(
        callable $setup,
        array $expectedInitialStates,
        array $expectedStates
    ) {
        $this->doTestFailedEventDrivenTest(
            function () use ($setup) {
                return $setup($this->testFactory);
            },
            $expectedInitialStates,
            function (TestFailedEvent $event) {
                $this->testCanceller->cancelAwaitingFromTestFailedEvent($event);
            },
            $expectedStates
        );
    }

    /**
     * @dataProvider cancelAwaitingFromTestFailedEventDataProvider
     *
     * @param callable $setup
     * @param array<Test::STATE_*> $expectedInitialStates
     * @param array<Test::STATE_*> $expectedStates
     */
    public function testSubscribesToTestExecuteDocumentReceivedEvent(
        callable $setup,
        array $expectedInitialStates,
        array $expectedStates
    ) {
        $this->doTestFailedEventDrivenTest(
            function () use ($setup) {
                return $setup($this->testFactory);
            },
            $expectedInitialStates,
            function (TestFailedEvent $event) {
                $this->eventDispatcher->dispatch($event);
            },
            $expectedStates
        );
    }

    public function cancelAwaitingFromTestFailedEventDataProvider(): array
    {
        return [
            'no awaiting tests, test not failed' => [
                'setup' => function (TestTestFactory $testFactory) {
                    return $this->createTestsWithStates($testFactory, [
                        Test::STATE_RUNNING,
                        Test::STATE_COMPLETE,
                    ]);
                },
                'expectedInitialStates' => [
                    Test::STATE_RUNNING,
                    Test::STATE_COMPLETE,
                ],
                'expectedStates' => [
                    Test::STATE_RUNNING,
                    Test::STATE_COMPLETE,
                ],
            ],
            'has awaiting tests, test not failed' => [
                'setup' => function (TestTestFactory $testFactory) {
                    return $this->createTestsWithStates($testFactory, [
                        Test::STATE_RUNNING,
                        Test::STATE_AWAITING,
                        Test::STATE_AWAITING,
                    ]);
                },
                'expectedInitialStates' => [
                    Test::STATE_RUNNING,
                    Test::STATE_AWAITING,
                    Test::STATE_AWAITING,
                ],
                'expectedStates' => [
                    Test::STATE_RUNNING,
                    Test::STATE_AWAITING,
                    Test::STATE_AWAITING,
                ],
            ],
            'no awaiting tests, test failed' => [
                'setup' => function (TestTestFactory $testFactory) {
                    return $this->createTestsWithStates($testFactory, [
                        Test::STATE_FAILED,
                        Test::STATE_COMPLETE,
                    ]);
                },
                'expectedInitialStates' => [
                    Test::STATE_FAILED,
                    Test::STATE_COMPLETE,
                ],
                'expectedStates' => [
                    Test::STATE_FAILED,
                    Test::STATE_COMPLETE,
                ],
            ],
            'has awaiting tests, test failed' => [
                'setup' => function (TestTestFactory $testFactory) {
                    return $this->createTestsWithStates($testFactory, [
                        Test::STATE_FAILED,
                        Test::STATE_AWAITING,
                        Test::STATE_AWAITING,
                    ]);
                },
                'expectedInitialStates' => [
                    Test::STATE_FAILED,
                    Test::STATE_AWAITING,
                    Test::STATE_AWAITING,
                ],
                'expectedStates' => [
                    Test::STATE_FAILED,
                    Test::STATE_CANCELLED,
                    Test::STATE_CANCELLED,
                ],
            ],
        ];
    }

    /**
     * @param callable $setup
     * @param array<Test::STATE_*> $expectedInitialStates
     * @param callable $execute
     * @param array<Test::STATE_*> $expectedStates
     */
    private function doTestFailedEventDrivenTest(
        callable $setup,
        array $expectedInitialStates,
        callable $execute,
        array $expectedStates
    ): void {
        /** @var Test[] $tests */
        $tests = $setup($this->testFactory);
        self::assertSame($this->testTestRepository->getStates(), $expectedInitialStates);

        $test = $tests[0];
        self::assertInstanceOf(Test::class, $test);

        $event = new TestFailedEvent($test);
        $execute($event);

        self::assertSame($this->testTestRepository->getStates(), $expectedStates);
    }

    /**
     * @param TestTestFactory $testFactory
     * @param array<Test::STATE_*> $states
     *
     * @return Test[]
     */
    private function createTestsWithStates(TestTestFactory $testFactory, array $states): array
    {
        $tests = [];

        foreach ($states as $state) {
            $tests[] = $testFactory->create(
                TestConfiguration::create('chrome', 'http://example.com'),
                '/app/source/Test/test.yml',
                '/app/tests/GeneratedTest.php',
                1,
                $state
            );
        }

        return $tests;
    }
}
