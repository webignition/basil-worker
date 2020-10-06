<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\TestConfiguration;
use App\Message\CompileSource;
use App\Services\CompilationWorkflowHandler;
use App\Services\JobStore;
use App\Services\TestStore;
use App\Tests\Functional\AbstractBaseFunctionalTest;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\InMemoryTransport;

class CompilationWorkflowHandlerTest extends AbstractBaseFunctionalTest
{
    private CompilationWorkflowHandler $handler;
    private JobStore $jobStore;
    private TestStore $testStore;
    private InMemoryTransport $messengerTransport;

    protected function setUp(): void
    {
        parent::setUp();

        $handler = self::$container->get(CompilationWorkflowHandler::class);
        if ($handler instanceof CompilationWorkflowHandler) {
            $this->handler = $handler;
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
     * @dataProvider dispatchNextCompileSourceMessageNoMessageDispatchedDataProvider
     */
    public function testDispatchNextCompileSourceMessageNoMessageDispatched(callable $initializer)
    {
        $initializer($this->jobStore, $this->testStore);

        $this->handler->dispatchNextCompileSourceMessage();

        self::assertCount(0, $this->messengerTransport->get());
    }

    public function dispatchNextCompileSourceMessageNoMessageDispatchedDataProvider(): array
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
     * @dataProvider dispatchNextCompileSourceMessageMessageDispatchedDataProvider
     */
    public function testDispatchNextCompileSourceMessageMessageDispatched(
        callable $initializer,
        CompileSource $expectedQueuedMessage
    ) {
        $initializer($this->jobStore, $this->testStore);

        $this->handler->dispatchNextCompileSourceMessage();

        $queue = $this->messengerTransport->get();
        self::assertCount(1, $queue);
        self::assertIsArray($queue);

        /** @var Envelope $envelope */
        $envelope = $queue[0] ?? null;

        self::assertEquals($expectedQueuedMessage, $envelope->getMessage());
    }

    public function dispatchNextCompileSourceMessageMessageDispatchedDataProvider(): array
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
