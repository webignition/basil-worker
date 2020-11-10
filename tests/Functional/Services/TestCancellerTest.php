<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Test;
use App\Entity\TestConfiguration;
use App\Services\TestCanceller;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\TestTestFactory;

class TestCancellerTest extends AbstractBaseFunctionalTest
{
    private TestCanceller $testCanceller;
    private TestTestFactory $testFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $testCanceller = self::$container->get(TestCanceller::class);
        self::assertInstanceOf(TestCanceller::class, $testCanceller);
        if ($testCanceller instanceof TestCanceller) {
            $this->testCanceller = $testCanceller;
        }

        $testFactory = self::$container->get(TestTestFactory::class);
        self::assertInstanceOf(TestTestFactory::class, $testFactory);
        if ($testFactory instanceof TestTestFactory) {
            $this->testFactory = $testFactory;
        }
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
        /** @var Test[] $tests */
        $tests = $setup($this->testFactory);

        foreach ($tests as $testIndex => $test) {
            $expectedInitialState = $expectedInitialStates[$testIndex] ?? null;
            self::assertSame($expectedInitialState, $test->getState());
        }

        $this->testCanceller->cancelAwaiting();

        foreach ($tests as $testIndex => $test) {
            $expectedState = $expectedStates[$testIndex] ?? null;
            self::assertSame($expectedState, $test->getState());
        }
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
