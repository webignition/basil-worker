<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

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

    protected function setUp(): void
    {
        parent::setUp();

        $handler = self::$container->get(ExecutionWorkflowHandler::class);
        if ($handler instanceof ExecutionWorkflowHandler) {
            $this->handler = $handler;
        }

        $jobStore = self::$container->get(JobStore::class);
        if ($jobStore instanceof JobStore) {
            $jobStore->create('label content', 'http://example.com/callback');
        }

        $messengerTransport = self::$container->get('messenger.transport.async');
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
        callable $expectedQueuedMessageCreator
    ) {
        $initializer($this->testFactory, $this->testStateMutator);

        $this->handler->dispatchNextExecuteTestMessage();

        $queue = $this->messengerTransport->get();
        self::assertCount(1, $queue);
        self::assertIsArray($queue);

        /** @var Envelope $envelope */
        $envelope = $queue[0] ?? null;

        self::assertEquals(
            $expectedQueuedMessageCreator($this->testRepository),
            $envelope->getMessage()
        );
    }

    public function dispatchNextExecuteTestMessageMessageDispatchedDataProvider(): array
    {
        return [
            'two tests, none run' => [
                'initializer' => function (TestTestFactory $testFactory) {
                    $testFactory->createFoo(
                        TestConfiguration::create('chrome', 'http://example.com'),
                        '/tests/test1.yml',
                        '/generated/GeneratedTest1.php',
                        1
                    );

                    $testFactory->createFoo(
                        TestConfiguration::create('chrome', 'http://example.com'),
                        '/tests/test2.yml',
                        '/generated/GeneratedTest2.php',
                        1
                    );
                },
                'expectedQueuedMessageCreator' => function (TestRepository $testRepository) {
                    $allTests = $testRepository->findAll();
                    $test = $allTests[0];

                    return new ExecuteTest((int) $test->getId());
                },
            ],
            'three tests, first complete' => [
                'initializer' => function (TestTestFactory $testFactory, TestStateMutator $testStateMutator) {
                    $firstTest = $testFactory->createFoo(
                        TestConfiguration::create('chrome', 'http://example.com'),
                        '/tests/test1.yml',
                        '/generated/GeneratedTest1.php',
                        1
                    );

                    $testStateMutator->setComplete($firstTest);

                    $testFactory->createFoo(
                        TestConfiguration::create('chrome', 'http://example.com'),
                        '/tests/test2.yml',
                        '/generated/GeneratedTest2.php',
                        1
                    );

                    $testFactory->createFoo(
                        TestConfiguration::create('chrome', 'http://example.com'),
                        '/tests/test3.yml',
                        '/generated/GeneratedTest3.php',
                        1
                    );
                },
                'expectedQueuedMessageCreator' => function (TestRepository $testRepository) {
                    $allTests = $testRepository->findAll();
                    $test = $allTests[1];

                    return new ExecuteTest((int) $test->getId());
                },
            ],
            'three tests, first, second complete' => [
                'initializer' => function (TestTestFactory $testFactory, TestStateMutator $testStateMutator) {
                    $firstTest = $testFactory->createFoo(
                        TestConfiguration::create('chrome', 'http://example.com'),
                        '/tests/test1.yml',
                        '/generated/GeneratedTest1.php',
                        1
                    );

                    $testStateMutator->setComplete($firstTest);

                    $secondTest = $testFactory->createFoo(
                        TestConfiguration::create('chrome', 'http://example.com'),
                        '/tests/test2.yml',
                        '/generated/GeneratedTest2.php',
                        1
                    );

                    $testStateMutator->setComplete($secondTest);

                    $testFactory->createFoo(
                        TestConfiguration::create('chrome', 'http://example.com'),
                        '/tests/test3.yml',
                        '/generated/GeneratedTest3.php',
                        1
                    );
                },
                'expectedQueuedMessageCreator' => function (TestRepository $testRepository) {
                    $allTests = $testRepository->findAll();
                    $test = $allTests[2];

                    return new ExecuteTest((int) $test->getId());
                },
            ],
        ];
    }
}
