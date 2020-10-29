<?php

declare(strict_types=1);

namespace App\Tests\Functional\EventSubscriber;

use App\Entity\Job;
use App\Entity\TestConfiguration;
use App\Event\SourceCompileSuccessEvent;
use App\EventSubscriber\SourceCompileSuccessEventSubscriber;
use App\Message\CompileSource;
use App\Services\JobStore;
use App\Services\TestStore;
use App\Tests\AbstractBaseFunctionalTest;
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
                SourceCompileSuccessEvent::NAME => [
                    ['createTests', 10],
                    ['dispatchNextCompileSourceMessage', 0],
                    ['setJobStateToCompilationAwaitingIfCompilationComplete', 0],
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

        $setup($this->jobStore, $testStore);

        self::assertCount($expectedInitialTestCount, $testStore->findAll());

        $eventDispatcher->dispatch($event, SourceCompileSuccessEvent::NAME);

        self::assertCount($expectedTestCount, $testStore->findAll());

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

        $testStore = self::$container->get(TestStore::class);
        self::assertInstanceOf(TestStore::class, $testStore);

        $messengerTransport = self::$container->get('messenger.transport.async');
        self::assertInstanceOf(InMemoryTransport::class, $messengerTransport);

        $setup($this->jobStore, $testStore);
        $job = $this->jobStore->getJob();

        self::assertNotSame(Job::STATE_EXECUTION_AWAITING, $job->getState());
        self::assertCount($expectedInitialTestCount, $testStore->findAll());

        $eventDispatcher->dispatch($event, SourceCompileSuccessEvent::NAME);

        self::assertCount($expectedTestCount, $testStore->findAll());
        self::assertCount(0, $messengerTransport->get());
        self::assertSame(Job::STATE_EXECUTION_AWAITING, $job->getState());
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
                'setup' => function (JobStore $jobStore, TestStore $testStore): void {
                    $job = $jobStore->getJob();
                    $job->setSources([
                        'Test/test1.yml',
                        'Test/test2.yml',
                    ]);
                    $jobStore->store($job);

                    $testStore->create(
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
