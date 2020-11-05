<?php

declare(strict_types=1);

namespace App\Tests\Functional\EventSubscriber;

use App\Entity\Job;
use App\Entity\Test;
use App\Entity\TestConfiguration;
use App\Event\TestExecuteCompleteEvent;
use App\Message\ExecuteTest;
use App\Repository\TestRepository;
use App\Services\JobStore;
use App\Services\TestStateMutator;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\TestTestFactory;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Transport\InMemoryTransport;

class TestExecuteCompleteEventSubscriberTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;

    private InMemoryTransport $messengerTransport;
    private Job $job;
    private TestStateMutator $testStateMutator;
    private TestRepository $testRepository;

    /**
     * @var Test[]
     */
    private array $tests = [];

    protected function setUp(): void
    {
        parent::setUp();

        $jobStore = self::$container->get(JobStore::class);
        self::assertInstanceOf(JobStore::class, $jobStore);

        $this->job = $jobStore->create('label content', 'http://example.com/callback');
        $this->job->setSources([
            'Test/test1.yml',
            'Test/test2.yml',
        ]);
        $this->job->setState(Job::STATE_EXECUTION_RUNNING);
        $jobStore->store($this->job);

        $testFactory = self::$container->get(TestTestFactory::class);
        self::assertInstanceOf(TestTestFactory::class, $testFactory);

        $testStateMutator = self::$container->get(TestStateMutator::class);
        self::assertInstanceOf(TestStateMutator::class, $testStateMutator);
        if ($testStateMutator instanceof TestStateMutator) {
            $this->testStateMutator = $testStateMutator;
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

        $this->tests[] = $testFactory->create(
            TestConfiguration::create('chrome', 'http://example.com'),
            '/app/source/Test/test1.yml',
            '/generated/GeneratedTest1.php',
            1
        );

        $this->tests[] = $testFactory->create(
            TestConfiguration::create('chrome', 'http://example.com'),
            '/app/source/Test/test2.yml',
            '/generated/GeneratedTest2.php',
            1
        );
    }

    /**
     * @dataProvider handleEventDataProvider
     */
    public function testHandleEvent(
        callable $setup,
        int $testIndex,
        string $expectedJobState,
        string $expectedTestState,
        int $expectedMessageQueueCount,
        ?callable $expectedMessageQueueMessageCreator = null
    ) {
        $setup($this->testStateMutator, $this->tests);
        $test = $this->tests[$testIndex];

        $eventDispatcher = self::$container->get(EventDispatcherInterface::class);
        if ($eventDispatcher instanceof EventDispatcherInterface) {
            $event = new TestExecuteCompleteEvent($test);

            $eventDispatcher->dispatch($event);
        }

        self::assertSame($expectedJobState, $this->job->getState());
        self::assertSame($expectedTestState, $test->getState());

        $queue = $this->messengerTransport->get();
        self::assertCount($expectedMessageQueueCount, $queue);

        if (1 === $expectedMessageQueueCount && is_callable($expectedMessageQueueMessageCreator)) {
            $expectedMessage = $expectedMessageQueueMessageCreator($this->testRepository);

            self::assertIsArray($queue);
            self::assertEquals($expectedMessage, $queue[0]->getMessage());
        }
    }

    public function handleEventDataProvider(): array
    {
        return [
            'test failed, not final test' => [
                'setup' => function (TestStateMutator $testStateMutator, array $tests) {
                    $testStateMutator->setFailed($tests[0]);
                },
                'testIndex' => 0,
                'expectedJobState' => Job::STATE_EXECUTION_COMPLETE,
                'expectedTestSTate' => Test::STATE_FAILED,
                'expectedMessageQueueCount' => 0,
            ],
            'test failed, is final test' => [
                'setup' => function (TestStateMutator $testStateMutator, array $tests) {
                    $testStateMutator->setComplete($tests[0]);
                    $testStateMutator->setFailed($tests[1]);
                },
                'testIndex' => 1,
                'expectedJobState' => Job::STATE_EXECUTION_COMPLETE,
                'expectedTestSTate' => Test::STATE_FAILED,
                'expectedMessageQueueCount' => 0,
            ],
            'test passed, not final test' => [
                'setup' => function (TestStateMutator $testStateMutator, array $tests) {
                    $testStateMutator->setRunning($tests[0]);
                },
                'testIndex' => 0,
                'expectedJobState' => Job::STATE_EXECUTION_RUNNING,
                'expectedTestSTate' => Test::STATE_COMPLETE,
                'expectedMessageQueueCount' => 1,
                'expectedMessageQueueMessageCreator' => function (TestRepository $testRepository) {
                    $nextAwaitingTest = $testRepository->findNextAwaiting();
                    $nextAwaitingId = $nextAwaitingTest instanceof Test ? (int) $nextAwaitingTest->getId() : 0;

                    return new ExecuteTest($nextAwaitingId);
                },
            ],
            'test passed, final test' => [
                'setup' => function (TestStateMutator $testStateMutator, array $tests) {
                    $testStateMutator->setComplete($tests[0]);
                    $testStateMutator->setRunning($tests[1]);
                },
                'testIndex' => 1,
                'expectedJobState' => Job::STATE_EXECUTION_COMPLETE,
                'expectedTestSTate' => Test::STATE_COMPLETE,
                'expectedMessageQueueCount' => 0,
            ],
        ];
    }
}
