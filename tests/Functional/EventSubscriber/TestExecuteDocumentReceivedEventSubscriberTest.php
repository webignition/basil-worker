<?php

declare(strict_types=1);

namespace App\Tests\Functional\EventSubscriber;

use App\Entity\Job;
use App\Entity\Test;
use App\Entity\TestConfiguration;
use App\Event\TestExecuteDocumentReceivedEvent;
use App\EventSubscriber\TestExecuteDocumentReceivedEventSubscriber;
use App\Message\SendCallback;
use App\Model\Callback\ExecuteDocumentReceived;
use App\Services\JobStore;
use App\Services\TestStore;
use App\Tests\AbstractBaseFunctionalTest;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Transport\InMemoryTransport;
use webignition\YamlDocument\Document;

class TestExecuteDocumentReceivedEventSubscriberTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;

    private TestExecuteDocumentReceivedEventSubscriber $eventSubscriber;
    private InMemoryTransport $messengerTransport;
    private Test $test;
    private JobStore $jobStore;

    protected function setUp(): void
    {
        parent::setUp();

        $jobStore = self::$container->get(JobStore::class);
        self::assertInstanceOf(JobStore::class, $jobStore);
        if ($jobStore instanceof JobStore) {
            $this->jobStore = $jobStore;
        }

        $job = $jobStore->create('label content', 'http://example.com/callback');
        $job->setState(Job::STATE_EXECUTION_RUNNING);
        $jobStore->store($job);

        $testStore = self::$container->get(TestStore::class);
        self::assertInstanceOf(TestStore::class, $testStore);

        $this->test = $testStore->create(
            TestConfiguration::create('chrome', 'http://example.com'),
            '/tests/test1.yml',
            '/generated/GeneratedTest1.php',
            1
        );

        $eventSubscriber = self::$container->get(TestExecuteDocumentReceivedEventSubscriber::class);
        if ($eventSubscriber instanceof TestExecuteDocumentReceivedEventSubscriber) {
            $this->eventSubscriber = $eventSubscriber;
        }

        $messengerTransport = self::$container->get('messenger.transport.async');
        if ($messengerTransport instanceof InMemoryTransport) {
            $this->messengerTransport = $messengerTransport;
        }
    }

    public function testDispatchSendCallbackMessage()
    {
        $document = new Document();
        $event = $this->createEvent($document);

        $this->eventSubscriber->dispatchSendCallbackMessage($event);

        $this->assertMessageTransportQueue($document);
    }

    /**
     * @dataProvider setTestStateToFailedIfStepFailedDataProvider
     */
    public function testSetTestStateToFailedIfStepFailed(Document $document, string $expectedTestState)
    {
        self::assertNotSame(Test::STATE_FAILED, $this->test->getState());

        $event = $this->createEvent($document);
        $this->eventSubscriber->setTestStateToFailedIfStepFailed($event);

        self::assertSame($expectedTestState, $this->test->getState());
    }

    public function setTestStateToFailedIfStepFailedDataProvider(): array
    {
        return [
            'not a step document' => [
                'document' => new Document('{ type: test }'),
                'expectedTestState' => Test::STATE_AWAITING,
            ],
            'step status passed' => [
                'document' => new Document('{ type: step, status: passed }'),
                'expectedTestState' => Test::STATE_AWAITING,
            ],
            'step status failed' => [
                'document' => new Document('{ type: step, status: failed }'),
                'expectedTestState' => Test::STATE_FAILED,
            ],
        ];
    }

    /**
     * @dataProvider integrationDataProvider
     */
    public function testIntegration(
        Document $document,
        string $expectedTestState,
        Document $expectedQueuedDocument,
        string $expectedJobState
    ) {
        self::assertNotSame(Test::STATE_FAILED, $this->test->getState());
        self::assertCount(0, $this->messengerTransport->get());

        $eventDispatcher = self::$container->get(EventDispatcherInterface::class);
        if ($eventDispatcher instanceof EventDispatcherInterface) {
            $event = $this->createEvent($document);

            $eventDispatcher->dispatch($event, TestExecuteDocumentReceivedEvent::NAME);
        }

        $this->assertMessageTransportQueue($expectedQueuedDocument);
        self::assertSame($expectedTestState, $this->test->getState());

        $job = $this->jobStore->getJob();
        self::assertSame($expectedJobState, $job->getState());
    }

    public function integrationDataProvider(): array
    {
        $testWithoutPrefixedPath = new Document('{ type: test, path: Test/test.yml }');
        $testWithPrefixedPath = new Document('{ type: test, path: /app/source/Test/test.yml }');

        $stepPassed = new Document('{ type: step, status: passed }');
        $stepFailed = new Document('{ type: step, status: failed }');

        return [
            'test document, path is not prefixed with compiler source directory' => [
                'document' => $testWithoutPrefixedPath,
                'expectedTestState' => Test::STATE_AWAITING,
                'expectedQueuedDocument' => $testWithoutPrefixedPath,
                'expectedJobState' => Job::STATE_EXECUTION_RUNNING,
            ],
            'test document, path prefixed with compiler source directory' => [
                'document' => $testWithPrefixedPath,
                'expectedTestState' => Test::STATE_AWAITING,
                'expectedQueuedDocument' => $testWithoutPrefixedPath,
                'expectedJobState' => Job::STATE_EXECUTION_RUNNING,
            ],
            'step status passed' => [
                'document' => $stepPassed,
                'expectedTestState' => Test::STATE_AWAITING,
                'expectedQueuedDocument' => $stepPassed,
                'expectedJobState' => Job::STATE_EXECUTION_RUNNING,
            ],
            'step status failed' => [
                'document' => $stepFailed,
                'expectedTestState' => Test::STATE_FAILED,
                'expectedQueuedDocument' => $stepFailed,
                'expectedJobState' => Job::STATE_EXECUTION_COMPLETE,
            ],
        ];
    }

    private function assertMessageTransportQueue(Document $document): void
    {
        $queue = $this->messengerTransport->get();
        self::assertCount(1, $queue);
        self::assertIsArray($queue);

        $expectedCallback = new ExecuteDocumentReceived($document);
        $expectedQueuedMessage = new SendCallback($expectedCallback);

        self::assertEquals($expectedQueuedMessage, $queue[0]->getMessage());
    }

    private function createEvent(Document $document): TestExecuteDocumentReceivedEvent
    {
        return new TestExecuteDocumentReceivedEvent($this->test, $document);
    }
}
