<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Test;
use App\Entity\TestConfiguration;
use App\Event\SourceCompile\SourceCompileSuccessEvent;
use App\Event\TestExecuteCompleteEvent;
use App\Message\ExecuteTest;
use App\Services\ExecutionWorkflowHandler;
use App\Services\JobStore;
use App\Services\TestStateMutator;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\MockSuiteManifest;
use App\Tests\Services\TestTestFactory;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\InMemoryTransport;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class ExecutionWorkflowHandlerTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private ExecutionWorkflowHandler $handler;
    private InMemoryTransport $messengerTransport;
    private TestTestFactory $testFactory;
    private TestStateMutator $testStateMutator;
    private JobStore $jobStore;
    private EventDispatcherInterface $eventDispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();

        $this->jobStore->create('label content', 'http://example.com/callback');
    }

    public function testDispatchNextExecuteTestMessageNoMessageDispatched()
    {
        $this->handler->dispatchNextExecuteTestMessage();

        self::assertCount(0, $this->messengerTransport->get());
    }

    /**
     * @dataProvider dispatchNextExecuteTestMessageMessageDispatchedDataProvider
     */
    public function testDispatchNextExecuteTestMessageMessageDispatched(
        callable $setup,
        int $expectedNextTestIndex
    ) {
        $this->doSourceCompileSuccessEventDrivenTest(
            function () use ($setup) {
                return $setup($this->jobStore, $this->testFactory, $this->testStateMutator);
            },
            function () {
                $this->handler->dispatchNextExecuteTestMessage();
            },
            $expectedNextTestIndex,
        );
    }

    public function dispatchNextExecuteTestMessageMessageDispatchedDataProvider(): array
    {
        return [
            'two tests, none run' => [
                'setup' => function (JobStore $jobStore, TestTestFactory $testFactory) {
                    $job = $jobStore->getJob();
                    $job->setSources([
                        'Test/test1.yml',
                        'Test/test2.yml',
                    ]);
                    $jobStore->store($job);

                    return [
                        $testFactory->create(
                            TestConfiguration::create('chrome', 'http://example.com'),
                            '/app/source/Test/test1.yml',
                            '/generated/GeneratedTest1.php',
                            1
                        ),
                        $testFactory->create(
                            TestConfiguration::create('chrome', 'http://example.com'),
                            '/app/source/Test/test2.yml',
                            '/generated/GeneratedTest2.php',
                            1
                        ),
                    ];
                },
                'expectedNextTestIndex' => 0,
            ],
            'three tests, first complete' => [
                'setup' => function (
                    JobStore $jobStore,
                    TestTestFactory $testFactory,
                    TestStateMutator $testStateMutator
                ) {
                    $job = $jobStore->getJob();
                    $job->setSources([
                        'Test/test1.yml',
                        'Test/test2.yml',
                        'Test/test3.yml',
                    ]);
                    $jobStore->store($job);

                    $tests = [
                        $testFactory->create(
                            TestConfiguration::create('chrome', 'http://example.com'),
                            '/app/source/Test/test1.yml',
                            '/generated/GeneratedTest1.php',
                            1
                        ),
                        $testFactory->create(
                            TestConfiguration::create('chrome', 'http://example.com'),
                            '/app/source/Test/test2.yml',
                            '/generated/GeneratedTest2.php',
                            1
                        ),
                        $testFactory->create(
                            TestConfiguration::create('chrome', 'http://example.com'),
                            '/app/source/Test/test3.yml',
                            '/generated/GeneratedTest3.php',
                            1
                        ),
                    ];

                    $testStateMutator->setRunning($tests[0]);
                    $testStateMutator->setComplete($tests[0]);

                    return $tests;
                },
                'expectedNextTestIndex' => 1,
            ],
            'three tests, first, second complete' => [
                'setup' => function (
                    JobStore $jobStore,
                    TestTestFactory $testFactory,
                    TestStateMutator $testStateMutator
                ) {
                    $job = $jobStore->getJob();
                    $job->setSources([
                        'Test/test1.yml',
                        'Test/test2.yml',
                        'Test/test3.yml',
                    ]);
                    $jobStore->store($job);

                    $tests = [
                        $testFactory->create(
                            TestConfiguration::create('chrome', 'http://example.com'),
                            '/app/source/Test/test1.yml',
                            '/generated/GeneratedTest1.php',
                            1
                        ),
                        $testFactory->create(
                            TestConfiguration::create('chrome', 'http://example.com'),
                            '/app/source/Test/test2.yml',
                            '/generated/GeneratedTest2.php',
                            1
                        ),
                        $testFactory->create(
                            TestConfiguration::create('chrome', 'http://example.com'),
                            '/app/source/Test/test3.yml',
                            '/generated/GeneratedTest3.php',
                            1
                        )
                    ];

                    $testStateMutator->setRunning($tests[0]);
                    $testStateMutator->setComplete($tests[0]);
                    $testStateMutator->setRunning($tests[1]);
                    $testStateMutator->setComplete($tests[1]);

                    return $tests;
                },
                'expectedNextTestIndex' => 2,
            ],
        ];
    }

    public function testSubscribesToSourceCompileSuccessEvent()
    {
        $this->doSourceCompileSuccessEventDrivenTest(
            function () {
                $job = $this->jobStore->getJob();
                $job->setSources([
                    'Test/test1.yml',
                    'Test/test2.yml',
                ]);
                $this->jobStore->store($job);

                return [
                    $this->testFactory->create(
                        TestConfiguration::create('chrome', 'http://example.com'),
                        '/app/source/Test/test1.yml',
                        '/generated/GeneratedTest1.php',
                        1,
                        Test::STATE_COMPLETE
                    ),
                    $this->testFactory->create(
                        TestConfiguration::create('chrome', 'http://example.com'),
                        '/app/source/Test/test2.yml',
                        '/generated/GeneratedTest2.php',
                        1,
                        Test::STATE_AWAITING
                    ),
                ];
            },
            function () {
                $this->eventDispatcher->dispatch(
                    new SourceCompileSuccessEvent(
                        '/app/source/Test/test1.yml',
                        (new MockSuiteManifest())
                            ->withGetTestManifestsCall([])
                            ->getMock()
                    ),
                );
            },
            1,
        );
    }

    private function doSourceCompileSuccessEventDrivenTest(
        callable $setup,
        callable $execute,
        int $expectedNextTestIndex
    ): void {
        self::assertCount(0, $this->messengerTransport->get());

        $tests = $setup();
        $execute();

        $queue = $this->messengerTransport->get();
        self::assertCount(1, $queue);
        self::assertIsArray($queue);

        $expectedNextTest = $tests[$expectedNextTestIndex] ?? null;
        self::assertInstanceOf(Test::class, $expectedNextTest);

        $envelope = $queue[0] ?? null;
        self::assertInstanceOf(Envelope::class, $envelope);
        self::assertEquals(
            new ExecuteTest((int) $expectedNextTest->getId()),
            $envelope->getMessage()
        );
    }

    /**
     * @dataProvider dispatchNextExecuteTestMessageFromTestExecuteCompleteEventDataProvider
     */
    public function testDispatchNextExecuteTestMessageFromTestExecuteCompleteEvent(
        callable $setup,
        int $eventTestIndex,
        int $expectedQueuedMessageCount,
        ?int $expectedNextTestIndex
    ) {
        $this->doTestExecuteCompleteEventDrivenTest(
            function () use ($setup) {
                return $setup($this->jobStore, $this->testFactory);
            },
            $eventTestIndex,
            function (TestExecuteCompleteEvent $event) {
                $this->handler->dispatchNextExecuteTestMessageFromTestExecuteCompleteEvent($event);
            },
            $expectedQueuedMessageCount,
            $expectedNextTestIndex
        );
    }

    public function dispatchNextExecuteTestMessageFromTestExecuteCompleteEventDataProvider(): array
    {
        return [
            'single test, not complete' => [
                'setup' => function (JobStore $jobStore, TestTestFactory $testFactory) {
                    $job = $jobStore->getJob();
                    $job->setSources([
                        'Test/test1.yml',
                    ]);
                    $jobStore->store($job);

                    return [
                        $testFactory->create(
                            TestConfiguration::create('chrome', 'http://example.com'),
                            '/app/source/Test/test1.yml',
                            '/generated/GeneratedTest1.php',
                            1,
                            Test::STATE_FAILED
                        ),
                    ];
                },
                'eventTestIndex' => 0,
                'expectedQueuedMessageCount' => 0,
                'expectedNextTestIndex' => null,
            ],
            'single test, is complete' => [
                'setup' => function (JobStore $jobStore, TestTestFactory $testFactory) {
                    $job = $jobStore->getJob();
                    $job->setSources([
                        'Test/test1.yml',
                    ]);
                    $jobStore->store($job);

                    return [
                        $testFactory->create(
                            TestConfiguration::create('chrome', 'http://example.com'),
                            '/app/source/Test/test1.yml',
                            '/generated/GeneratedTest1.php',
                            1,
                            Test::STATE_COMPLETE
                        ),
                    ];
                },
                'eventTestIndex' => 0,
                'expectedQueuedMessageCount' => 0,
                'expectedNextTestIndex' => null,
            ],
            'multiple tests, not complete' => [
                'setup' => function (JobStore $jobStore, TestTestFactory $testFactory) {
                    $job = $jobStore->getJob();
                    $job->setSources([
                        'Test/test1.yml',
                        'Test/test2.yml',
                    ]);
                    $jobStore->store($job);

                    return [
                        $testFactory->create(
                            TestConfiguration::create('chrome', 'http://example.com'),
                            '/app/source/Test/test1.yml',
                            '/generated/GeneratedTest1.php',
                            1,
                            Test::STATE_FAILED
                        ),
                        $testFactory->create(
                            TestConfiguration::create('chrome', 'http://example.com'),
                            '/app/source/Test/test2.yml',
                            '/generated/GeneratedTest2.php',
                            1
                        ),
                    ];
                },
                'eventTestIndex' => 0,
                'expectedQueuedMessageCount' => 0,
                'expectedNextTestIndex' => null,
            ],
            'multiple tests, complete' => [
                'setup' => function (JobStore $jobStore, TestTestFactory $testFactory) {
                    $job = $jobStore->getJob();
                    $job->setSources([
                        'Test/test1.yml',
                        'Test/test2.yml',
                    ]);
                    $jobStore->store($job);

                    return [
                        $testFactory->create(
                            TestConfiguration::create('chrome', 'http://example.com'),
                            '/app/source/Test/test1.yml',
                            '/generated/GeneratedTest1.php',
                            1,
                            Test::STATE_COMPLETE
                        ),
                        $testFactory->create(
                            TestConfiguration::create('chrome', 'http://example.com'),
                            '/app/source/Test/test2.yml',
                            '/generated/GeneratedTest2.php',
                            1
                        ),
                    ];
                },
                'eventTestIndex' => 0,
                'expectedQueuedMessageCount' => 1,
                'expectedNextTestIndex' => 1,
            ],
        ];
    }

    public function testSubscribesToTestExecuteCompleteEvent()
    {
        $this->doTestExecuteCompleteEventDrivenTest(
            function () {
                $job = $this->jobStore->getJob();
                $job->setSources([
                    'Test/test1.yml',
                    'Test/test2.yml',
                ]);
                $this->jobStore->store($job);

                return [
                    $this->testFactory->create(
                        TestConfiguration::create('chrome', 'http://example.com'),
                        '/app/source/Test/test1.yml',
                        '/generated/GeneratedTest1.php',
                        1,
                        Test::STATE_COMPLETE
                    ),
                    $this->testFactory->create(
                        TestConfiguration::create('chrome', 'http://example.com'),
                        '/app/source/Test/test2.yml',
                        '/generated/GeneratedTest2.php',
                        1
                    )
                ];
            },
            0,
            function (TestExecuteCompleteEvent $event) {
                $this->eventDispatcher->dispatch($event);
            },
            1,
            1
        );
    }

    private function doTestExecuteCompleteEventDrivenTest(
        callable $setup,
        int $eventTestIndex,
        callable $execute,
        int $expectedQueuedMessageCount,
        ?int $expectedNextTestIndex
    ): void {
        $tests = $setup($this->jobStore, $this->testFactory);
        self::assertCount(0, $this->messengerTransport->get());

        $test = $tests[$eventTestIndex];
        $event = new TestExecuteCompleteEvent($test);

        $execute($event);

        $queue = $this->messengerTransport->get();
        self::assertIsArray($queue);
        self::assertCount($expectedQueuedMessageCount, $queue);

        if (is_int($expectedNextTestIndex)) {
            $expectedNextTest = $tests[$expectedNextTestIndex] ?? null;
            self::assertInstanceOf(Test::class, $expectedNextTest);

            $envelope = $queue[0] ?? null;
            self::assertInstanceOf(Envelope::class, $envelope);
            self::assertEquals(
                new ExecuteTest((int) $expectedNextTest->getId()),
                $envelope->getMessage()
            );
        }
    }
}
