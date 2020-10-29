<?php

declare(strict_types=1);

namespace App\Tests\Functional\EventSubscriber;

use App\Entity\Test;
use App\Entity\TestConfiguration;
use App\Event\TestExecuteDocumentReceivedEvent;
use App\EventSubscriber\TestExecuteDocumentReceivedEventSubscriber;
use App\Message\SendCallback;
use App\Model\Callback\ExecuteDocumentReceived;
use App\Services\JobStore;
use App\Services\TestStore;
use App\Tests\Functional\AbstractBaseFunctionalTest;
use App\Tests\Mock\MockYamlDocument;
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

    protected function setUp(): void
    {
        parent::setUp();

        $jobStore = self::$container->get(JobStore::class);
        self::assertInstanceOf(JobStore::class, $jobStore);

        $jobStore->create('label content', 'http://example.com/callback');

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
        $document = (new MockYamlDocument())->getMock();
        $event = $this->createEvent($document);

        $this->eventSubscriber->dispatchSendCallbackMessage($event);

        $this->assertMessageTransportQueue($document);
    }

    /**
     * @dataProvider setTestStateToFailedIfFailedDataProvider
     */
    public function testSetTestStateToFailedIfFailed(Document $document, string $expectedTestState)
    {
        self::assertNotSame(Test::STATE_FAILED, $this->test->getState());

        $event = $this->createEvent($document);
        $this->eventSubscriber->setTestStateToFailedIfFailed($event);

        self::assertSame($expectedTestState, $this->test->getState());
    }

    public function setTestStateToFailedIfFailedDataProvider(): array
    {
        return [
            'not a step document' => [
                'document' => (new MockYamlDocument())
                    ->withParseCall([
                        'type' => 'test',
                    ])
                    ->getMock(),
                'expectedTestState' => Test::STATE_AWAITING,
            ],
            'step status passed' => [
                'document' => (new MockYamlDocument())
                    ->withParseCall([
                        'type' => 'step',
                        'status' => 'passed',
                    ])
                    ->getMock(),
                'expectedTestState' => Test::STATE_AWAITING,
            ],
            'step status failed' => [
                'document' => (new MockYamlDocument())
                    ->withParseCall([
                        'type' => 'step',
                        'status' => 'failed',
                    ])
                    ->getMock(),
                'expectedTestState' => Test::STATE_FAILED,
            ],
        ];
    }

    /**
     * @dataProvider integrationDataProvider
     */
    public function testIntegration(Document $document, string $expectedTestState)
    {
        self::assertNotSame(Test::STATE_FAILED, $this->test->getState());
        self::assertCount(0, $this->messengerTransport->get());

        $eventDispatcher = self::$container->get(EventDispatcherInterface::class);
        if ($eventDispatcher instanceof EventDispatcherInterface) {
            $event = $this->createEvent($document);

            $eventDispatcher->dispatch($event, TestExecuteDocumentReceivedEvent::NAME);
        }

        $this->assertMessageTransportQueue($document);
        self::assertSame($expectedTestState, $this->test->getState());
    }

    public function integrationDataProvider(): array
    {
        return [
            'not a step document' => [
                'document' => (new MockYamlDocument())
                    ->withParseCall([
                        'type' => 'test',
                    ])
                    ->getMock(),
                'expectedTestState' => Test::STATE_AWAITING,
            ],
            'step status passed' => [
                'document' => (new MockYamlDocument())
                    ->withParseCall([
                        'type' => 'step',
                        'status' => 'passed',
                    ])
                    ->getMock(),
                'expectedTestState' => Test::STATE_AWAITING,
            ],
            'step status failed' => [
                'document' => (new MockYamlDocument())
                    ->withParseCall([
                        'type' => 'step',
                        'status' => 'failed',
                    ])
                    ->getMock(),
                'expectedTestState' => Test::STATE_FAILED,
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
