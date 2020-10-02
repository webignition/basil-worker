<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageHandler;

use App\Entity\Job;
use App\Entity\TestConfiguration;
use App\Event\SourcesAddedEvent;
use App\EventSubscriber\SourcesAddedEventSubscriber;
use App\Message\CompileSource;
use App\Services\JobStore;
use App\Services\TestStore;
use App\Tests\Functional\AbstractBaseFunctionalTest;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\InMemoryTransport;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class SourcesAddedEventSubscriberTest extends AbstractBaseFunctionalTest
{
    private SourcesAddedEventSubscriber $eventSubscriber;
    private JobStore $jobStore;
    private TestStore $testStore;
    private InMemoryTransport $messengerTransport;

    protected function setUp(): void
    {
        parent::setUp();

        $eventSubscriber = self::$container->get(SourcesAddedEventSubscriber::class);
        if ($eventSubscriber instanceof SourcesAddedEventSubscriber) {
            $this->eventSubscriber = $eventSubscriber;
        }

        $jobStore = self::$container->get(JobStore::class);
        if ($jobStore instanceof JobStore) {
            $jobStore->create('label content', 'http://example.com/callback');
            $this->jobStore = $jobStore;
        }

        $testStore = self::$container->get(TestStore::class);
        if ($testStore instanceof TestStore) {
            $this->testStore = $testStore;
        }

        $messengerTransport = self::$container->get('messenger.transport.async');
        if ($messengerTransport instanceof InMemoryTransport) {
            $this->messengerTransport = $messengerTransport;
        }
    }

    public function testGetSubscribedEvents()
    {
        self::assertSame(
            [
                SourcesAddedEvent::NAME => [
                    ['setJobState', 10],
                    ['dispatchCompileSourceMessage', 0]
                ],
            ],
            SourcesAddedEventSubscriber::getSubscribedEvents()
        );
    }

    public function testSetJobState()
    {
        $job = $this->jobStore->getJob();
        self::assertSame(Job::STATE_COMPILATION_AWAITING, $job->getState());

        $this->eventSubscriber->setJobState();

        $this->assertJobState($job);
    }

    /**
     * @dataProvider dispatchCompileSourceMessageNoMessageDispatchedDataProvider
     */
    public function testDispatchCompileSourceMessageNoMessageDispatched(callable $initializer)
    {
        $initializer($this->jobStore, $this->testStore);

        $this->eventSubscriber->dispatchCompileSourceMessage();

        self::assertCount(0, $this->messengerTransport->get());
    }

    public function dispatchCompileSourceMessageNoMessageDispatchedDataProvider(): array
    {
        return [
            'no sources' => [
                'initializer' => function () {
                },
            ],
            'no non-compiled sources' => [
                'initializer' => function (JobStore $jobStore, TestStore $testStore) {
                    $job = $jobStore->getJob();
                    $job->setSources([
                        'Test/test1.yml',
                    ]);
                    $jobStore->store();

                    $testStore->create(
                        TestConfiguration::create('chrome', 'http://example.com'),
                        'Test/test1.yml',
                        '/app/tests/GeneratedTest1.php',
                        1
                    );
                },
            ],
        ];
    }

    /**
     * @dataProvider dispatchCompileSourceMessageMessageDispatchedDataProvider
     */
    public function testDispatchCompileSourceMessageMessageDispatched(
        callable $initializer,
        CompileSource $expectedQueuedMessage
    ) {
        $initializer($this->jobStore, $this->testStore);

        $this->eventSubscriber->dispatchCompileSourceMessage();

        $this->assertMessageTransportQueue($expectedQueuedMessage);
    }

    public function dispatchCompileSourceMessageMessageDispatchedDataProvider(): array
    {
        return [
            'no sources compiled' => [
                'initializer' => function (JobStore $jobStore) {
                    $job = $jobStore->getJob();
                    $job->setSources([
                        'Test/test1.yml',
                        'Test/test2.yml',
                    ]);
                    $jobStore->store();
                },
                'expectedQueuedMessage' => new CompileSource('Test/test1.yml'),
            ],
            'all but one sources compiled' => [
                'initializer' => function (JobStore $jobStore, TestStore $testStore) {
                    $job = $jobStore->getJob();
                    $job->setSources([
                        'Test/test1.yml',
                        'Test/test2.yml',
                    ]);
                    $jobStore->store();

                    $testStore->create(
                        TestConfiguration::create('chrome', 'http://example.com'),
                        'Test/test1.yml',
                        '/app/tests/GeneratedTest1.php',
                        1
                    );
                },
                'expectedQueuedMessage' => new CompileSource('Test/test2.yml'),
            ],
        ];
    }

    public function testIntegration()
    {
        $eventDispatcher = self::$container->get(EventDispatcherInterface::class);
        self::assertInstanceOf(EventDispatcherInterface::class, $eventDispatcher);

        $job = $this->jobStore->getJob();
        $job->setSources([
            'Test/test1.yml',
        ]);
        $this->jobStore->store();

        $eventDispatcher->dispatch(new SourcesAddedEvent(), SourcesAddedEvent::NAME);

        $expectedQueuedMessage = new CompileSource('Test/test1.yml');

        $this->assertJobState($job);
        $this->assertMessageTransportQueue($expectedQueuedMessage);
    }

    private function assertJobState(Job $job): void
    {
        self::assertSame(Job::STATE_COMPILATION_RUNNING, $job->getState());
    }

    private function assertMessageTransportQueue(CompileSource $expectedQueuedMessage): void
    {
        $queue = $this->messengerTransport->get();
        self::assertCount(1, $queue);
        self::assertIsArray($queue);

        /** @var Envelope $envelope */
        $envelope = $queue[0] ?? null;

        self::assertEquals($expectedQueuedMessage, $envelope->getMessage());
    }
}
