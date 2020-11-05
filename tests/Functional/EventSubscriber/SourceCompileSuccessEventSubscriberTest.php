<?php

declare(strict_types=1);

namespace App\Tests\Functional\EventSubscriber;

use App\Entity\Job;
use App\Entity\Test;
use App\Entity\TestConfiguration;
use App\Event\SourceCompile\SourceCompileSuccessEvent;
use App\EventSubscriber\SourceCompileSuccessEventSubscriber;
use App\Message\CompileSource;
use App\Message\ExecuteTest;
use App\Repository\TestRepository;
use App\Services\JobStore;
use App\Services\TestStore;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\TestTestFactory;
use Symfony\Component\Messenger\Transport\InMemoryTransport;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use webignition\BasilCompilerModels\ConfigurationInterface;
use webignition\BasilCompilerModels\SuiteManifest;
use webignition\BasilCompilerModels\TestManifest;
use webignition\BasilModels\Test\Configuration;

class SourceCompileSuccessEventSubscriberTest extends AbstractBaseFunctionalTest
{
    private JobStore $jobStore;

    protected function setUp(): void
    {
        parent::setUp();

        $jobStore = self::$container->get(JobStore::class);
        if ($jobStore instanceof JobStore) {
            $jobStore->create('label content', 'http://example.com/callback');
            $this->jobStore = $jobStore;
        }
    }

    public function testGetSubscribedEvents()
    {
        self::assertSame(
            [
                SourceCompileSuccessEvent::class => [
                    ['createTests', 30],
                    ['dispatchNextCompileSourceMessage', 20],
                    ['setJobStateToExecutionAwaitingIfCompilationComplete', 10],
                    ['dispatchNextTestExecuteMessage', 0],
                ],
            ],
            SourceCompileSuccessEventSubscriber::getSubscribedEvents()
        );
    }

    /**
     * @dataProvider integrationNotFinalEventDataProvider
     */
    public function testIntegrationNotFinalEvent(
        callable $setup,
        int $expectedInitialTestCount,
        SourceCompileSuccessEvent $event,
        int $expectedTestCount,
        CompileSource $expectedQueuedMessage
    ) {
        $eventDispatcher = self::$container->get(EventDispatcherInterface::class);
        self::assertInstanceOf(EventDispatcherInterface::class, $eventDispatcher);

        $testStore = self::$container->get(TestStore::class);
        self::assertInstanceOf(TestStore::class, $testStore);

        $messengerTransport = self::$container->get('messenger.transport.async');
        self::assertInstanceOf(InMemoryTransport::class, $messengerTransport);

        $testRepository = self::$container->get(TestRepository::class);
        self::assertInstanceOf(TestRepository::class, $testRepository);

        $setup($this->jobStore, $testStore);

        self::assertCount($expectedInitialTestCount, $testRepository->findAll());

        $eventDispatcher->dispatch($event);

        self::assertCount($expectedTestCount, $testRepository->findAll());

        $queue = $messengerTransport->get();
        self::assertCount(1, $queue);
        self::assertIsArray($queue);

        $envelope = $queue[0] ?? null;
        self::assertEquals($expectedQueuedMessage, $envelope->getMessage());
    }

    public function integrationNotFinalEventDataProvider(): array
    {
        return [
            'two sources, event is for first' => [
                'setup' => function (JobStore $jobStore): void {
                    $job = $jobStore->getJob();
                    $job->setSources([
                        'Test/test1.yml',
                        'Test/test2.yml',
                    ]);
                    $jobStore->store($job);
                },
                'expectedInitialTestCount' => 0,
                'event' => new SourceCompileSuccessEvent(
                    'Test/test1.yml',
                    new SuiteManifest(
                        \Mockery::mock(ConfigurationInterface::class),
                        [
                            new TestManifest(
                                new Configuration('chrome', 'http://example.com'),
                                '/app/source/Test/test1.yml',
                                '/app/tests/GeneratedChromeTest.php',
                                2
                            ),
                            new TestManifest(
                                new Configuration('firefox', 'http://example.com'),
                                '/app/source/Test/test1.yml',
                                '/app/tests/GeneratedFirefoxTest.php',
                                2
                            ),
                        ]
                    )
                ),
                'expectedTestCount' => 2,
                'expectedQueuedMessage' => new CompileSource('Test/test2.yml'),
            ],
            'two sources, event is for second' => [
                'setup' => function (JobStore $jobStore): void {
                    $job = $jobStore->getJob();
                    $job->setSources([
                        'Test/test1.yml',
                        'Test/test2.yml',
                    ]);
                    $jobStore->store($job);
                },
                'expectedInitialTestCount' => 0,
                'event' => new SourceCompileSuccessEvent(
                    'Test/test2.yml',
                    new SuiteManifest(
                        \Mockery::mock(ConfigurationInterface::class),
                        [
                            new TestManifest(
                                new Configuration('chrome', 'http://example.com'),
                                '/app/source/Test/test2.yml',
                                '/app/tests/GeneratedChromeTest.php',
                                2
                            ),
                            new TestManifest(
                                new Configuration('firefox', 'http://example.com'),
                                '/app/source/Test/test2.yml',
                                '/app/tests/GeneratedFirefoxTest.php',
                                2
                            ),
                        ]
                    )
                ),
                'expectedTestCount' => 2,
                'expectedQueuedMessage' => new CompileSource('Test/test1.yml'),
            ],
        ];
    }

    /**
     * @dataProvider integrationFinalEventDataProvider
     */
    public function testIntegrationFinalEvent(
        callable $setup,
        int $expectedInitialTestCount,
        SourceCompileSuccessEvent $event,
        int $expectedTestCount
    ) {
        $eventDispatcher = self::$container->get(EventDispatcherInterface::class);
        self::assertInstanceOf(EventDispatcherInterface::class, $eventDispatcher);

        $testFactory = self::$container->get(TestTestFactory::class);
        self::assertInstanceOf(TestTestFactory::class, $testFactory);

        $messengerTransport = self::$container->get('messenger.transport.async');
        self::assertInstanceOf(InMemoryTransport::class, $messengerTransport);

        $testRepository = self::$container->get(TestRepository::class);
        self::assertInstanceOf(TestRepository::class, $testRepository);

        $setup($this->jobStore, $testFactory);
        $job = $this->jobStore->getJob();

        self::assertNotSame(Job::STATE_EXECUTION_AWAITING, $job->getState());
        self::assertCount($expectedInitialTestCount, $testRepository->findAll());

        $eventDispatcher->dispatch($event);

        self::assertCount($expectedTestCount, $testRepository->findAll());
        self::assertSame(Job::STATE_EXECUTION_AWAITING, $job->getState());

        $queue = $messengerTransport->get();
        self::assertCount(1, $queue);
        self::assertIsArray($queue);

        $nextAwaitingTest = $testRepository->findNextAwaiting();
        $nextAwaitingTestId = $nextAwaitingTest instanceof Test ? (int) $nextAwaitingTest->getId() : 0;

        $expectedQueuedMessage = new ExecuteTest($nextAwaitingTestId);

        $envelope = $queue[0] ?? null;
        self::assertEquals($expectedQueuedMessage, $envelope->getMessage());
    }

    public function integrationFinalEventDataProvider(): array
    {
        return [
            'single source' => [
                'setup' => function (JobStore $jobStore): void {
                    $job = $jobStore->getJob();
                    $job->setSources([
                        'Test/test1.yml',
                    ]);
                    $jobStore->store($job);
                },
                'expectedInitialTestCount' => 0,
                'event' => new SourceCompileSuccessEvent(
                    'Test/test1.yml',
                    new SuiteManifest(
                        \Mockery::mock(ConfigurationInterface::class),
                        [
                            new TestManifest(
                                new Configuration('chrome', 'http://example.com'),
                                '/app/source/Test/test1.yml',
                                '/app/tests/GeneratedTest1.php',
                                2
                            ),
                        ]
                    )
                ),
                'expectedTestCount' => 1,
            ],
            'two sources, first is compiled, event is for second' => [
                'setup' => function (JobStore $jobStore, TestTestFactory $testFactory): void {
                    $job = $jobStore->getJob();
                    $job->setSources([
                        'Test/test1.yml',
                        'Test/test2.yml',
                    ]);
                    $jobStore->store($job);

                    $testFactory->createFoo(
                        TestConfiguration::create('chrome', 'http://example.com/one'),
                        '/app/source/Test/test1.yml',
                        '/app/tests/GeneratedTest1.php',
                        3
                    );
                },
                'expectedInitialTestCount' => 1,
                'event' => new SourceCompileSuccessEvent(
                    'Test/test2.yml',
                    new SuiteManifest(
                        \Mockery::mock(ConfigurationInterface::class),
                        [
                            new TestManifest(
                                new Configuration('chrome', 'http://example.com/two'),
                                '/app/source/Test/test2.yml',
                                '/app/tests/GeneratedTest2.php',
                                2
                            ),
                        ]
                    )
                ),
                'expectedTestCount' => 2,
            ],
        ];
    }
}
