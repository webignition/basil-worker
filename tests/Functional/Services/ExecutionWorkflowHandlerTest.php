<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Test;
use App\Entity\TestConfiguration;
use App\Message\ExecuteTest;
use App\Repository\TestRepository;
use App\Services\ExecutionWorkflowHandler;
use App\Services\JobStore;
use App\Services\TestStateMutator;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\TestTestFactory;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\InMemoryTransport;

class ExecutionWorkflowHandlerTest extends AbstractBaseFunctionalTest
{
    private ExecutionWorkflowHandler $handler;
    private InMemoryTransport $messengerTransport;
    private TestTestFactory $testFactory;
    private TestStateMutator $testStateMutator;
    private TestRepository $testRepository;
    private JobStore $jobStore;

    protected function setUp(): void
    {
        parent::setUp();

        $handler = self::$container->get(ExecutionWorkflowHandler::class);
        self::assertInstanceOf(ExecutionWorkflowHandler::class, $handler);
        if ($handler instanceof ExecutionWorkflowHandler) {
            $this->handler = $handler;
        }

        $jobStore = self::$container->get(JobStore::class);
        self::assertInstanceOf(JobStore::class, $jobStore);
        if ($jobStore instanceof JobStore) {
            $jobStore->create('label content', 'http://example.com/callback');
            $this->jobStore = $jobStore;
        }

        $messengerTransport = self::$container->get('messenger.transport.async');
        self::assertInstanceOf(InMemoryTransport::class, $messengerTransport);
        if ($messengerTransport instanceof InMemoryTransport) {
            $this->messengerTransport = $messengerTransport;
        }

        $testFactory = self::$container->get(TestTestFactory::class);
        self::assertInstanceOf(TestTestFactory::class, $testFactory);
        if ($testFactory instanceof TestTestFactory) {
            $this->testFactory = $testFactory;
        }

        $testStateMutator = self::$container->get(TestStateMutator::class);
        self::assertInstanceOf(TestStateMutator::class, $testStateMutator);
        if ($testStateMutator instanceof TestStateMutator) {
            $this->testStateMutator = $testStateMutator;
        }

        $testRepository = self::$container->get(TestRepository::class);
        self::assertInstanceOf(TestRepository::class, $testRepository);
        if ($testRepository instanceof TestRepository) {
            $this->testRepository = $testRepository;
        }
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
        callable $initializer,
        int $expectedNextTestIndex
    ) {
        $this->doSourceCompileSuccessEventDrivenTest(
            function () use ($initializer) {
                $initializer($this->jobStore, $this->testFactory, $this->testStateMutator);
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
                'initializer' => function (JobStore $jobStore, TestTestFactory $testFactory) {
                    $job = $jobStore->getJob();
                    $job->setSources([
                        'Test/test1.yml',
                        'Test/test2.yml',
                    ]);
                    $jobStore->store($job);

                    $testFactory->create(
                        TestConfiguration::create('chrome', 'http://example.com'),
                        '/app/source/Test/test1.yml',
                        '/generated/GeneratedTest1.php',
                        1
                    );

                    $testFactory->create(
                        TestConfiguration::create('chrome', 'http://example.com'),
                        '/app/source/Test/test2.yml',
                        '/generated/GeneratedTest2.php',
                        1
                    );
                },
                'expectedNextTestIndex' => 0,
            ],
            'three tests, first complete' => [
                'initializer' => function (
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

                    $firstTest = $testFactory->create(
                        TestConfiguration::create('chrome', 'http://example.com'),
                        '/app/source/Test/test1.yml',
                        '/generated/GeneratedTest1.php',
                        1
                    );

                    $testStateMutator->setRunning($firstTest);
                    $testStateMutator->setComplete($firstTest);

                    $testFactory->create(
                        TestConfiguration::create('chrome', 'http://example.com'),
                        '/app/source/Test/test2.yml',
                        '/generated/GeneratedTest2.php',
                        1
                    );

                    $testFactory->create(
                        TestConfiguration::create('chrome', 'http://example.com'),
                        '/app/source/Test/test3.yml',
                        '/generated/GeneratedTest3.php',
                        1
                    );
                },
                'expectedNextTestIndex' => 1,
            ],
            'three tests, first, second complete' => [
                'initializer' => function (
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

                    $firstTest = $testFactory->create(
                        TestConfiguration::create('chrome', 'http://example.com'),
                        '/app/source/Test/test1.yml',
                        '/generated/GeneratedTest1.php',
                        1
                    );

                    $testStateMutator->setRunning($firstTest);
                    $testStateMutator->setComplete($firstTest);


                    $secondTest = $testFactory->create(
                        TestConfiguration::create('chrome', 'http://example.com'),
                        '/app/source/Test/test2.yml',
                        '/generated/GeneratedTest2.php',
                        1
                    );

                    $testStateMutator->setRunning($secondTest);
                    $testStateMutator->setComplete($secondTest);

                    $testFactory->create(
                        TestConfiguration::create('chrome', 'http://example.com'),
                        '/app/source/Test/test3.yml',
                        '/generated/GeneratedTest3.php',
                        1
                    );
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
                ]);
                $this->jobStore->store($job);

                $this->testFactory->create(
                    TestConfiguration::create('chrome', 'http://example.com'),
                    '/app/source/Test/test1.yml',
                    '/generated/GeneratedTest1.php',
                    1
                );
            },
            function () {
                $this->handler->dispatchNextExecuteTestMessage();
            },
            0,
        );
    }

    private function doSourceCompileSuccessEventDrivenTest(
        callable $setup,
        callable $execute,
        int $expectedNextTestIndex
    ): void {
        self::assertCount(0, $this->messengerTransport->get());

        $setup();
        $execute();

        $queue = $this->messengerTransport->get();
        self::assertCount(1, $queue);
        self::assertIsArray($queue);

        $allTests = $this->testRepository->findAll();
        $expectedNextTest = $allTests[$expectedNextTestIndex] ?? null;
        self::assertInstanceOf(Test::class, $expectedNextTest);

        $envelope = $queue[0] ?? null;
        self::assertInstanceOf(Envelope::class, $envelope);
        self::assertEquals(
            new ExecuteTest((int) $expectedNextTest->getId()),
            $envelope->getMessage()
        );
    }
}
