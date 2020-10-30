<?php

declare(strict_types=1);

namespace App\Tests\Functional\EventSubscriber;

use App\Entity\Job;
use App\Entity\Test;
use App\Entity\TestConfiguration;
use App\Event\TestExecuteCompleteEvent;
use App\Message\ExecuteTest;
use App\Services\JobStore;
use App\Services\TestStore;
use App\Tests\AbstractBaseFunctionalTest;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Transport\InMemoryTransport;

class TestExecuteCompleteEventSubscriberTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;

    private InMemoryTransport $messengerTransport;
    private TestStore $testStore;
    private Job $job;

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

        $testStore = self::$container->get(TestStore::class);
        self::assertInstanceOf(TestStore::class, $testStore);

        if ($testStore instanceof TestStore) {
            $this->testStore = $testStore;
        }

        $this->tests[] = $testStore->create(
            TestConfiguration::create('chrome', 'http://example.com'),
            '/app/source/Test/test1.yml',
            '/generated/GeneratedTest1.php',
            1
        );

        $this->tests[] = $testStore->create(
            TestConfiguration::create('chrome', 'http://example.com'),
            '/app/source/Test/test2.yml',
            '/generated/GeneratedTest2.php',
            1
        );

        $messengerTransport = self::$container->get('messenger.transport.async');
        if ($messengerTransport instanceof InMemoryTransport) {
            $this->messengerTransport = $messengerTransport;
        }
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
        $setup($this->testStore, $this->tests);
        $test = $this->tests[$testIndex];

        $eventDispatcher = self::$container->get(EventDispatcherInterface::class);
        if ($eventDispatcher instanceof EventDispatcherInterface) {
            $event = new TestExecuteCompleteEvent($test);

            $eventDispatcher->dispatch($event, TestExecuteCompleteEvent::NAME);
        }

        self::assertSame($expectedJobState, $this->job->getState());
        self::assertSame($expectedTestState, $test->getState());

        $queue = $this->messengerTransport->get();
        self::assertCount($expectedMessageQueueCount, $queue);

        if (1 === $expectedMessageQueueCount && is_callable($expectedMessageQueueMessageCreator)) {
            $expectedMessage = $expectedMessageQueueMessageCreator($this->testStore);

            self::assertIsArray($queue);
            self::assertEquals($expectedMessage, $queue[0]->getMessage());
        }
    }

    public function handleEventDataProvider(): array
    {
        return [
            'test failed, not final test' => [
                'setup' => function (TestStore $testStore, array $tests) {
                    $test = $tests[0];
                    $test->setState(Test::STATE_FAILED);
                    $testStore->store($test);
                },
                'testIndex' => 0,
                'expectedJobState' => Job::STATE_EXECUTION_COMPLETE,
                'expectedTestSTate' => Test::STATE_FAILED,
                'expectedMessageQueueCount' => 0,
            ],
            'test failed, is final test' => [
                'setup' => function (TestStore $testStore, array $tests) {
                    $tests[0]->setState(Test::STATE_COMPLETE);
                    $testStore->store($tests[0]);

                    $tests[1]->setState(Test::STATE_FAILED);
                    $testStore->store($tests[1]);
                },
                'testIndex' => 1,
                'expectedJobState' => Job::STATE_EXECUTION_COMPLETE,
                'expectedTestSTate' => Test::STATE_FAILED,
                'expectedMessageQueueCount' => 0,
            ],
            'test passed, not final test' => [
                'setup' => function (TestStore $testStore, array $tests) {
                    $test = $tests[0];
                    $test->setState(Test::STATE_RUNNING);
                    $testStore->store($test);
                },
                'testIndex' => 0,
                'expectedJobState' => Job::STATE_EXECUTION_RUNNING,
                'expectedTestSTate' => Test::STATE_COMPLETE,
                'expectedMessageQueueCount' => 1,
                'expectedMessageQueueMessageCreator' => function (TestStore $testStore) {
                    $nextAwaitingTest = $testStore->findNextAwaiting();
                    $nextAwaitingId = $nextAwaitingTest instanceof Test ? (int) $nextAwaitingTest->getId() : 0;

                    return new ExecuteTest($nextAwaitingId);
                },
            ],
            'test passed, final test' => [
                'setup' => function (TestStore $testStore, array $tests) {
                    $tests[0]->setState(Test::STATE_COMPLETE);
                    $testStore->store($tests[0]);

                    $test = $tests[1];
                    $test->setState(Test::STATE_RUNNING);
                    $testStore->store($test);
                },
                'testIndex' => 1,
                'expectedJobState' => Job::STATE_EXECUTION_COMPLETE,
                'expectedTestSTate' => Test::STATE_COMPLETE,
                'expectedMessageQueueCount' => 0,
            ],
        ];
    }
}
