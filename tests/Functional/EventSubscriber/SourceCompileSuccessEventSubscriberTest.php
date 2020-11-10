<?php

declare(strict_types=1);

namespace App\Tests\Functional\EventSubscriber;

use App\Entity\Job;
use App\Entity\Test;
use App\Entity\TestConfiguration;
use App\Event\SourceCompile\SourceCompileSuccessEvent;
use App\EventSubscriber\SourceCompileSuccessEventSubscriber;
use App\Message\ExecuteTest;
use App\Repository\TestRepository;
use App\Services\JobStore;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\TestTestFactory;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\InMemoryTransport;
use webignition\BasilCompilerModels\ConfigurationInterface;
use webignition\BasilCompilerModels\SuiteManifest;
use webignition\BasilCompilerModels\TestManifest;
use webignition\BasilModels\Test\Configuration;

class SourceCompileSuccessEventSubscriberTest extends AbstractBaseFunctionalTest
{
    private JobStore $jobStore;
    private EventDispatcherInterface $eventDispatcher;
    private TestTestFactory $testFactory;
    private InMemoryTransport $messengerTransport;
    private TestRepository $testRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $jobStore = self::$container->get(JobStore::class);
        self::assertInstanceOf(JobStore::class, $jobStore);
        if ($jobStore instanceof JobStore) {
            $jobStore->create('label content', 'http://example.com/callback');
            $this->jobStore = $jobStore;
        }

        $eventDispatcher = self::$container->get(EventDispatcherInterface::class);
        self::assertInstanceOf(EventDispatcherInterface::class, $eventDispatcher);
        if ($eventDispatcher instanceof EventDispatcherInterface) {
            $this->eventDispatcher = $eventDispatcher;
        }

        $testFactory = self::$container->get(TestTestFactory::class);
        self::assertInstanceOf(TestTestFactory::class, $testFactory);
        if ($testFactory instanceof TestTestFactory) {
            $this->testFactory = $testFactory;
        }

        $messengerTransport = self::$container->get('messenger.transport.async');
        self::assertInstanceOf(InMemoryTransport::class, $messengerTransport);
        if ($messengerTransport instanceof InMemoryTransport) {
            $this->messengerTransport = $messengerTransport;
        }

        $testRepository = self::$container->get(TestRepository::class);
        self::assertInstanceOf(TestRepository::class, $testRepository);
        if ($testRepository instanceof TestRepository) {
            $this->testRepository = $testRepository;
        }
    }

    public function testGetSubscribedEvents()
    {
        self::assertSame(
            [
                SourceCompileSuccessEvent::class => [
                    ['dispatchNextTestExecuteMessage', 0],
                ],
            ],
            SourceCompileSuccessEventSubscriber::getSubscribedEvents()
        );
    }

    /**
     * @dataProvider integrationDataProvider
     */
    public function testIntegration(callable $setup, SourceCompileSuccessEvent $event)
    {
        $setup($this->jobStore, $this->testFactory);
        $job = $this->jobStore->getJob();

        self::assertNotSame(Job::STATE_EXECUTION_AWAITING, $job->getState());

        $this->eventDispatcher->dispatch($event);

        self::assertSame(Job::STATE_EXECUTION_AWAITING, $job->getState());

        $queue = $this->messengerTransport->get();
        self::assertCount(1, $queue);
        self::assertIsArray($queue);

        $nextAwaitingTest = $this->testRepository->findNextAwaiting();
        $nextAwaitingTestId = $nextAwaitingTest instanceof Test ? (int) $nextAwaitingTest->getId() : 0;

        $expectedQueuedMessage = new ExecuteTest($nextAwaitingTestId);

        $envelope = $queue[0] ?? null;
        self::assertInstanceOf(Envelope::class, $envelope);
        self::assertEquals($expectedQueuedMessage, $envelope->getMessage());
    }

    public function integrationDataProvider(): array
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
            ],
            'two sources, first is compiled, event is for second' => [
                'setup' => function (JobStore $jobStore, TestTestFactory $testFactory): void {
                    $job = $jobStore->getJob();
                    $job->setSources([
                        'Test/test1.yml',
                        'Test/test2.yml',
                    ]);
                    $jobStore->store($job);

                    $testFactory->create(
                        TestConfiguration::create('chrome', 'http://example.com/one'),
                        '/app/source/Test/test1.yml',
                        '/app/tests/GeneratedTest1.php',
                        3
                    );
                },
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
            ],
        ];
    }
}
