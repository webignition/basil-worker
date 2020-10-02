<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageHandler;

use App\Entity\TestConfiguration;
use App\EventSubscriber\SourcesAddedEventSubscriber;
use App\Message\CompileSource;
use App\Services\JobStore;
use App\Services\TestStore;
use App\Tests\Functional\AbstractBaseFunctionalTest;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\InMemoryTransport;

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

    /**
     * @dataProvider onSourcesAddedNoMessageDispatchedDataProvider
     */
    public function testOnSourcesAddedNoMessageDispatched(callable $initializer)
    {
        $initializer($this->jobStore, $this->testStore);

        $this->eventSubscriber->onSourcesAdded();

        self::assertCount(0, $this->messengerTransport->get());
    }

    public function onSourcesAddedNoMessageDispatchedDataProvider(): array
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
     * @dataProvider onSourcesAddedMessageDispatchedDataProvider
     */
    public function testOnSourcesAddedMessageDispatched(
        callable $initializer,
        ?CompileSource $expectedQueuedMessage
    ) {
        $initializer($this->jobStore, $this->testStore);

        $this->eventSubscriber->onSourcesAdded();

        $queue = $this->messengerTransport->get();
        self::assertCount(1, $queue);
        self::assertIsArray($queue);

        /** @var Envelope $envelope */
        $envelope = $queue[0] ?? null;

        self::assertEquals($expectedQueuedMessage, $envelope->getMessage());
    }

    public function onSourcesAddedMessageDispatchedDataProvider(): array
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
}
