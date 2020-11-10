<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Test;
use App\Entity\TestConfiguration;
use App\Event\TestExecuteCompleteEvent;
use App\Event\TestExecuteDocumentReceivedEvent;
use App\Services\TestStateMutator;
use App\Services\TestStore;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\TestTestFactory;
use Psr\EventDispatcher\EventDispatcherInterface;
use webignition\YamlDocument\Document;

class TestStateMutatorTest extends AbstractBaseFunctionalTest
{
    private TestStateMutator $mutator;
    private EventDispatcherInterface $eventDispatcher;
    private Test $test;
    private TestStore $testStore;

    protected function setUp(): void
    {
        parent::setUp();

        $mutator = self::$container->get(TestStateMutator::class);
        self::assertInstanceOf(TestStateMutator::class, $mutator);
        if ($mutator instanceof TestStateMutator) {
            $this->mutator = $mutator;
        }

        $testFactory = self::$container->get(TestTestFactory::class);
        self::assertInstanceOf(TestTestFactory::class, $testFactory);
        if ($testFactory instanceof TestTestFactory) {
            $this->test = $testFactory->create(
                TestConfiguration::create('chrome', 'http://example.com/callback'),
                '/app/source/Test/test.yml',
                '/app/tests/GeneratedTest.php',
                1
            );
        }

        $eventDispatcher = self::$container->get(EventDispatcherInterface::class);
        self::assertInstanceOf(EventDispatcherInterface::class, $eventDispatcher);
        if ($eventDispatcher instanceof EventDispatcherInterface) {
            $this->eventDispatcher = $eventDispatcher;
        }

        $testStore = self::$container->get(TestStore::class);
        self::assertInstanceOf(TestStore::class, $testStore);
        if ($testStore instanceof TestStore) {
            $this->testStore = $testStore;
        }
    }

    /**
     * @dataProvider setCompleteDataProvider
     *
     * @param Test::STATE_* $initialState
     * @param Test::STATE_* $expectedState
     */
    public function testSetComplete(string $initialState, string $expectedState)
    {
        $this->test->setState($initialState);
        $this->testStore->store($this->test);

        self::assertSame($initialState, $this->test->getState());

        $this->mutator->setComplete($this->test);

        self::assertSame($expectedState, $this->test->getState());
    }

    public function setCompleteDataProvider(): array
    {
        return [
            Test::STATE_AWAITING => [
                'initialState' => Test::STATE_AWAITING,
                'expectedState' => Test::STATE_AWAITING,
            ],
            Test::STATE_RUNNING => [
                'initialState' => Test::STATE_RUNNING,
                'expectedState' => Test::STATE_COMPLETE,
            ],
            Test::STATE_COMPLETE => [
                'initialState' => Test::STATE_COMPLETE,
                'expectedState' => Test::STATE_COMPLETE,
            ],
            Test::STATE_FAILED => [
                'initialState' => Test::STATE_FAILED,
                'expectedState' => Test::STATE_FAILED,
            ],
            Test::STATE_CANCELLED => [
                'initialState' => Test::STATE_CANCELLED,
                'expectedState' => Test::STATE_CANCELLED,
            ],
        ];
    }

    public function testSetCompleteFromTestExecuteCompleteEvent()
    {
        $this->doTestExecuteCompleteEventDrivenTest(function (TestExecuteCompleteEvent $event) {
            $this->mutator->setCompleteFromTestExecuteCompleteEvent($event);
        });
    }

    public function testSubscribesToTestExecuteCompleteEvent()
    {
        $this->doTestExecuteCompleteEventDrivenTest(function (TestExecuteCompleteEvent $event) {
            $this->eventDispatcher->dispatch($event);
        });
    }

    private function doTestExecuteCompleteEventDrivenTest(callable $callable): void
    {
        $this->test->setState(Test::STATE_RUNNING);
        $this->testStore->store($this->test);

        $event = new TestExecuteCompleteEvent($this->test);

        $callable($event);

        self::assertSame(Test::STATE_COMPLETE, $this->test->getState());
    }

    /**
     * @dataProvider handleTestExecuteDocumentReceivedEventDataProvider
     */
    public function testSetFailedFromTestExecuteDocumentReceivedEvent(Document $document, string $expectedState)
    {
        $this->doTestExecuteDocumentReceivedEventDrivenTest(
            $document,
            $expectedState,
            function (TestExecuteDocumentReceivedEvent $event) {
                $this->mutator->setFailedFromTestExecuteDocumentReceivedEvent($event);
            }
        );
    }

    /**
     * @dataProvider handleTestExecuteDocumentReceivedEventDataProvider
     */
    public function testSubscribesToTestExecuteDocumentReceivedEvent(Document $document, string $expectedState)
    {
        $this->doTestExecuteDocumentReceivedEventDrivenTest(
            $document,
            $expectedState,
            function (TestExecuteDocumentReceivedEvent $event) {
                $this->eventDispatcher->dispatch($event);
            }
        );
    }

    private function doTestExecuteDocumentReceivedEventDrivenTest(
        Document $document,
        string $expectedState,
        callable $execute
    ): void {
        self::assertSame(Test::STATE_AWAITING, $this->test->getState());

        $event = new TestExecuteDocumentReceivedEvent($this->test, $document);
        $execute($event);

        self::assertSame($expectedState, $this->test->getState());
    }

    public function handleTestExecuteDocumentReceivedEventDataProvider(): array
    {
        return [
            'not a step' => [
                'document' => new Document('{ type: test }'),
                'expectedState' => Test::STATE_AWAITING,
            ],
            'step passed' => [
                'document' => new Document('{ type: step, status: passed }'),
                'expectedState' => Test::STATE_AWAITING,
            ],
            'step failed' => [
                'document' => new Document('{ type: step, status: failed }'),
                'expectedState' => Test::STATE_FAILED,
            ],
        ];
    }
}
