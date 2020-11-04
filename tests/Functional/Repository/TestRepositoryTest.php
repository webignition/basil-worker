<?php

declare(strict_types=1);

namespace App\Tests\Functional\Repository;

use App\Entity\Test;
use App\Entity\TestConfiguration;
use App\Repository\TestRepository;
use App\Services\TestConfigurationStore;
use App\Tests\AbstractBaseFunctionalTest;
use Doctrine\ORM\EntityManagerInterface;
use webignition\ObjectReflector\ObjectReflector;

class TestRepositoryTest extends AbstractBaseFunctionalTest
{
    private EntityManagerInterface $entityManager;
    private TestRepository $repository;
    private TestConfigurationStore $testConfigurationStore;

    protected function setUp(): void
    {
        parent::setUp();

        $entityManager = self::$container->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        if ($entityManager instanceof EntityManagerInterface) {
            $this->entityManager = $entityManager;
        }

        $repository = self::$container->get(TestRepository::class);
        self::assertInstanceOf(TestRepository::class, $repository);
        if ($repository instanceof TestRepository) {
            $this->repository = $repository;
        }

        $testConfigurationStore = self::$container->get(TestConfigurationStore::class);
        self::assertInstanceOf(TestConfigurationStore::class, $testConfigurationStore);
        if ($testConfigurationStore instanceof TestConfigurationStore) {
            $this->testConfigurationStore = $testConfigurationStore;
        }
    }

    /**
     * @dataProvider findAllDataProvider
     *
     * @param callable $testsCreator
     * @param Test[] $expectedTests
     */
    public function testFindAll(callable $testsCreator, array $expectedTests)
    {
        $tests = $testsCreator($this->testConfigurationStore);
        $this->persistTests($tests);

        $allTests = $this->repository->findAll();
        self::assertCount(count($expectedTests), $allTests);

        foreach ($expectedTests as $expectedTestIndex => $expectedTest) {
            $test = $allTests[$expectedTestIndex];

            self::assertSame($expectedTest->getConfiguration()->getUrl(), $test->getConfiguration()->getUrl());
            self::assertSame($expectedTest->getPosition(), $test->getPosition());
        }
    }

    public function findAllDataProvider(): array
    {
        $tests = [
            'position1' => $this->createTest(
                TestConfiguration::create('chrome', 'http://example.com/1'),
                '/app/source/Test/test1.yml',
                'app/tests/GeneratedTest1.php',
                1
            ),
            'position2' => $this->createTest(
                TestConfiguration::create('chrome', 'http://example.com/2'),
                '/app/source/Test/test2.yml',
                'app/tests/GeneratedTest2.php',
                2
            ),
            'position3' => $this->createTest(
                TestConfiguration::create('chrome', 'http://example.com/3'),
                '/app/source/Test/test3.yml',
                'app/tests/GeneratedTest3.php',
                3
            ),
        ];

        return [
            'empty' => [
                'setup' => function () {
                    return [];
                },
                'expectedTests' => [],
            ],
            'one' => [
                'setup' => function (TestConfigurationStore $testConfigurationStore) use ($tests) {
                    return $this->createTests(
                        [
                            $tests['position1'],
                        ],
                        $testConfigurationStore
                    );
                },
                'expectedTests' => [
                    $tests['position1']
                ],
            ],
            'many, created in position order' => [
                'setup' => function (TestConfigurationStore $testConfigurationStore) use ($tests) {
                    return $this->createTests(
                        [
                            $tests['position1'],
                            $tests['position2'],
                            $tests['position3'],
                        ],
                        $testConfigurationStore
                    );
                },
                'expectedTests' => [
                    $tests['position1'],
                    $tests['position2'],
                    $tests['position3'],
                ],
            ],
            'many, created in reverse position order' => [
                'setup' => function (TestConfigurationStore $testConfigurationStore) use ($tests) {
                    return $this->createTests(
                        [
                            $tests['position3'],
                            $tests['position2'],
                            $tests['position1'],
                        ],
                        $testConfigurationStore
                    );
                },
                'expectedTests' => [
                    $tests['position1'],
                    $tests['position2'],
                    $tests['position3'],
                ],
            ],
            'many, created in arbitrary position order' => [
                'setup' => function (TestConfigurationStore $testConfigurationStore) use ($tests) {
                    return $this->createTests(
                        [
                            $tests['position2'],
                            $tests['position3'],
                            $tests['position1'],
                        ],
                        $testConfigurationStore
                    );
                },
                'expectedTests' => [
                    $tests['position1'],
                    $tests['position2'],
                    $tests['position3'],
                ],
            ],
        ];
    }

    public function testFindBySource()
    {
        $tests = $this->createTests(
            [
                '/app/source/Test/test1.yml' => $this->createTest(
                    TestConfiguration::create('chrome', 'http://example.com/1'),
                    '/app/source/Test/test1.yml',
                    'app/tests/GeneratedTest1.php',
                    1
                ),
                '/app/source/Test/test2.yml' => $this->createTest(
                    TestConfiguration::create('chrome', 'http://example.com/2'),
                    '/app/source/Test/test2.yml',
                    'app/tests/GeneratedTest2.php',
                    2
                ),
            ],
            $this->testConfigurationStore
        );

        $this->persistTests($tests);

        self::assertNull($this->repository->findBySource(''));
        self::assertNull($this->repository->findBySource('/app/source/Test/test3.yml'));

        foreach ($tests as $source => $expectedTest) {
            $test = $this->repository->findBySource($source);
            self::assertInstanceOf(Test::class, $test);

            self::assertSame($source, $test->getSource());
            self::assertSame($expectedTest->getSource(), $test->getSource());
        }
    }

    /**
     * @dataProvider findMaxPositionDataProvider
     */
    public function testFindMaxPosition(callable $testsCreator, ?int $expectedMaxPosition)
    {
        $tests = $testsCreator($this->testConfigurationStore);
        $this->persistTests($tests);

        self::assertSame($expectedMaxPosition, $this->repository->findMaxPosition());
    }

    public function findMaxPositionDataProvider(): array
    {
        $tests = [
            'position1' => $this->createTest(
                TestConfiguration::create('chrome', 'http://example.com/1'),
                '',
                '',
                1
            ),
            'position2' => $this->createTest(
                TestConfiguration::create('chrome', 'http://example.com/2'),
                '',
                '',
                2
            ),
            'position3' => $this->createTest(
                TestConfiguration::create('chrome', 'http://example.com/3'),
                '',
                '',
                3
            ),
        ];

        return [
            'empty' => [
                'setup' => function () {
                    return [];
                },
                'expectedMaxPosition' => null,
            ],
            'one test, position 1' => [
                'setup' => function (TestConfigurationStore $testConfigurationStore) use ($tests) {
                    return $this->createTests(
                        [
                            $tests['position1'],
                        ],
                        $testConfigurationStore
                    );
                },
                'expectedMaxPosition' => 1,
            ],
            'one test, position 3' => [
                'setup' => function (TestConfigurationStore $testConfigurationStore) use ($tests) {
                    return $this->createTests(
                        [
                            $tests['position3'],
                        ],
                        $testConfigurationStore
                    );
                },
                'expectedMaxPosition' => 3,
            ],
            'three tests, position 1, 2, 3' => [
                'setup' => function (TestConfigurationStore $testConfigurationStore) use ($tests) {
                    return $this->createTests(
                        [
                            $tests['position1'],
                            $tests['position2'],
                            $tests['position3'],
                        ],
                        $testConfigurationStore
                    );
                },
                'expectedMaxPosition' => 3,
            ],
            'three tests, position 3, 2, 1' => [
                'setup' => function (TestConfigurationStore $testConfigurationStore) use ($tests) {
                    return $this->createTests(
                        [
                            $tests['position3'],
                            $tests['position2'],
                            $tests['position1'],
                        ],
                        $testConfigurationStore
                    );
                },
                'expectedMaxPosition' => 3,
            ],
            'three tests, position 1, 3, 2' => [
                'setup' => function (TestConfigurationStore $testConfigurationStore) use ($tests) {
                    return $this->createTests(
                        [
                            $tests['position1'],
                            $tests['position3'],
                            $tests['position2'],
                        ],
                        $testConfigurationStore
                    );
                },
                'expectedMaxPosition' => 3,
            ],
        ];
    }

    /**
     * @dataProvider findNextAwaitingDataProvider
     */
    public function testFindNextAwaiting(callable $testsCreator, ?Test $expectedNextAwaiting)
    {
        $tests = $testsCreator($this->testConfigurationStore);
        $this->persistTests($tests);

        $nextAwaiting = $this->repository->findNextAwaiting();

        if ($expectedNextAwaiting instanceof Test) {
            self::assertInstanceOf(Test::class, $nextAwaiting);
            self::assertSame(
                $expectedNextAwaiting->getConfiguration()->getUrl(),
                $nextAwaiting->getConfiguration()->getUrl()
            );
            self::assertSame($expectedNextAwaiting->getPosition(), $nextAwaiting->getPosition());
        } else {
            self::assertNull($nextAwaiting);
        }
    }

    public function findNextAwaitingDataProvider(): array
    {
        $tests = [
            'awaiting1' => $this->createTest(
                TestConfiguration::create('chrome', 'http://example.com/awaiting1'),
                '',
                '',
                1,
                Test::STATE_AWAITING
            ),
            'awaiting2' => $this->createTest(
                TestConfiguration::create('chrome', 'http://example.com/awaiting2'),
                '',
                '',
                2,
                Test::STATE_AWAITING
            ),
            'running' => $this->createTest(
                TestConfiguration::create('chrome', 'http://example.com/running'),
                '',
                '',
                3,
                Test::STATE_FAILED
            ),
            'failed' => $this->createTest(
                TestConfiguration::create('chrome', 'http://example.com/failed'),
                '',
                '',
                4,
                Test::STATE_FAILED
            ),
            'complete' => $this->createTest(
                TestConfiguration::create('chrome', 'http://example.com/complete'),
                '',
                '',
                5,
                Test::STATE_COMPLETE
            ),
        ];

        return [
            'empty' => [
                'setup' => function () {
                    return [];
                },
                'expectedNextAwaiting' => null,
            ],
            'awaiting1' => [
                'setup' => function (TestConfigurationStore $testConfigurationStore) use ($tests) {
                    return $this->createTests(
                        [
                            $tests['awaiting1'],
                        ],
                        $testConfigurationStore
                    );
                },
                'expectedNextAwaiting' => $tests['awaiting1'],
            ],
            'awaiting2' => [
                'setup' => function (TestConfigurationStore $testConfigurationStore) use ($tests) {
                    return $this->createTests(
                        [
                            $tests['awaiting2'],
                        ],
                        $testConfigurationStore
                    );
                },
                'expectedNextAwaiting' => $tests['awaiting2'],
            ],
            'awaiting1, awaiting2' => [
                'setup' => function (TestConfigurationStore $testConfigurationStore) use ($tests) {
                    return $this->createTests(
                        [
                            $tests['awaiting1'],
                            $tests['awaiting2'],
                        ],
                        $testConfigurationStore
                    );
                },
                'expectedNextAwaiting' => $tests['awaiting1'],
            ],
            'awaiting2, awaiting1' => [
                'setup' => function (TestConfigurationStore $testConfigurationStore) use ($tests) {
                    return $this->createTests(
                        [
                            $tests['awaiting2'],
                            $tests['awaiting1'],
                        ],
                        $testConfigurationStore
                    );
                },
                'expectedNextAwaiting' => $tests['awaiting1'],
            ],
            'running, failed, complete' => [
                'setup' => function (TestConfigurationStore $testConfigurationStore) use ($tests) {
                    return $this->createTests(
                        [
                            $tests['running'],
                            $tests['failed'],
                            $tests['complete'],
                        ],
                        $testConfigurationStore
                    );
                },
                'expectedNextAwaiting' => null,
            ],
        ];
    }

    /**
     * @dataProvider getAwaitingCountDataProvider
     */
    public function testGetAwaitingCount(callable $testsCreator, int $expectedAwaitingCount)
    {
        $tests = $testsCreator($this->testConfigurationStore);
        $this->persistTests($tests);

        self::assertSame($expectedAwaitingCount, $this->repository->getAwaitingCount());
    }

    public function getAwaitingCountDataProvider(): array
    {
        $tests = [
            'awaiting1' => $this->createTest(
                TestConfiguration::create('chrome', 'http://example.com/awaiting1'),
                '',
                '',
                1,
                Test::STATE_AWAITING
            ),
            'awaiting2' => $this->createTest(
                TestConfiguration::create('chrome', 'http://example.com/awaiting2'),
                '',
                '',
                2,
                Test::STATE_AWAITING
            ),
            'running' => $this->createTest(
                TestConfiguration::create('chrome', 'http://example.com/running'),
                '',
                '',
                3,
                Test::STATE_FAILED
            ),
            'failed' => $this->createTest(
                TestConfiguration::create('chrome', 'http://example.com/failed'),
                '',
                '',
                4,
                Test::STATE_FAILED
            ),
            'complete' => $this->createTest(
                TestConfiguration::create('chrome', 'http://example.com/complete'),
                '',
                '',
                5,
                Test::STATE_COMPLETE
            ),
        ];

        return [
            'empty' => [
                'setup' => function () {
                    return [];
                },
                'expectedAwaitingCount' => 0,
            ],
            'awaiting1' => [
                'setup' => function (TestConfigurationStore $testConfigurationStore) use ($tests) {
                    return $this->createTests(
                        [
                            $tests['awaiting1'],
                        ],
                        $testConfigurationStore
                    );
                },
                'expectedAwaitingCount' => 1,
            ],
            'awaiting2' => [
                'setup' => function (TestConfigurationStore $testConfigurationStore) use ($tests) {
                    return $this->createTests(
                        [
                            $tests['awaiting2'],
                        ],
                        $testConfigurationStore
                    );
                },
                'expectedAwaitingCount' => 1,
            ],
            'awaiting1, awaiting2' => [
                'setup' => function (TestConfigurationStore $testConfigurationStore) use ($tests) {
                    return $this->createTests(
                        [
                            $tests['awaiting1'],
                            $tests['awaiting2'],
                        ],
                        $testConfigurationStore
                    );
                },
                'expectedAwaitingCount' => 2,
            ],
            'running, failed, complete' => [
                'setup' => function (TestConfigurationStore $testConfigurationStore) use ($tests) {
                    return $this->createTests(
                        [
                            $tests['running'],
                            $tests['failed'],
                            $tests['complete'],
                        ],
                        $testConfigurationStore
                    );
                },
                'expectedAwaitingCount' => 0,
            ],
        ];
    }

    /**
     * @param Test[] $tests
     */
    private function persistTests(array $tests): void
    {
        foreach ($tests as $test) {
            $this->entityManager->persist($test);
            $this->entityManager->flush();
        }
    }

    /**
     * @param Test[] $tests
     * @param TestConfigurationStore $configurationStore
     *
     * @return Test[]
     */
    private function createTests(array $tests, TestConfigurationStore $configurationStore): array
    {
        array_walk($tests, function (Test &$test) use ($configurationStore) {
            $configuration = $configurationStore->findByConfiguration($test->getConfiguration());
            ObjectReflector::setProperty($test, Test::class, 'configuration', $configuration);
        });

        return $tests;
    }

    /**
     * @param TestConfiguration $configuration
     * @param string $source
     * @param string $target
     * @param int $position
     * @param Test::STATE_* $state
     *
     * @return Test
     */
    private function createTest(
        TestConfiguration $configuration,
        string $source,
        string $target,
        int $position,
        string $state = Test::STATE_AWAITING
    ): Test {
        $test = Test::create(
            $configuration,
            $source,
            $target,
            1,
            $position
        );

        $test->setState($state);

        return $test;
    }
}
