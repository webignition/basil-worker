<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Event\TestStepFailedEvent;
use App\Services\TestStateMutator;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Model\TestSetup;
use App\Tests\Services\TestTestFactory;
use App\Tests\Services\TestTestMutator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use App\Entity\Test;
use webignition\YamlDocument\Document;

class TestStateMutatorTest extends AbstractBaseFunctionalTest
{
    private TestStateMutator $mutator;
    private EventDispatcherInterface $eventDispatcher;
    private TestTestMutator $testMutator;
    private Test $test;

    protected function setUp(): void
    {
        parent::setUp();

        $testStateMutator = self::$container->get(TestStateMutator::class);
        \assert($testStateMutator instanceof TestStateMutator);
        $this->mutator = $testStateMutator;

        $eventDispatcher = self::$container->get(EventDispatcherInterface::class);
        \assert($eventDispatcher instanceof EventDispatcherInterface);
        $this->eventDispatcher = $eventDispatcher;

        $testMutator = self::$container->get(TestTestMutator::class);
        \assert($testMutator instanceof TestTestMutator);
        $this->testMutator = $testMutator;

        $testTestFactory = self::$container->get(TestTestFactory::class);
        \assert($testTestFactory instanceof TestTestFactory);
        $this->test = $testTestFactory->create(new TestSetup());
    }

    /**
     * @dataProvider setCompleteIfRunningDataProvider
     *
     * @param Test::STATE_* $initialState
     * @param Test::STATE_* $expectedState
     */
    public function testSetCompleteIfRunning(string $initialState, string $expectedState): void
    {
        $this->testMutator->setState($this->test, $initialState);
        self::assertSame($initialState, $this->test->getState());

        $this->mutator->setCompleteIfRunning($this->test);

        self::assertSame($expectedState, $this->test->getState());
    }

    /**
     * @return array[]
     */
    public function setCompleteIfRunningDataProvider(): array
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

    /**
     * @dataProvider handleTestStepFailedEventDataProvider
     */
    public function testSetFailedFromTestStepFailedEventEvent(Document $document, string $expectedState): void
    {
        $this->doTestExecuteDocumentReceivedEventDrivenTest(
            $document,
            $expectedState,
            function (TestStepFailedEvent $event) {
                $this->mutator->setFailedFromTestStepFailedEvent($event);
            }
        );
    }

    /**
     * @dataProvider handleTestStepFailedEventDataProvider
     */
    public function testSubscribesToTestStepFailedEvent(Document $document, string $expectedState): void
    {
        $this->doTestExecuteDocumentReceivedEventDrivenTest(
            $document,
            $expectedState,
            function (TestStepFailedEvent $event) {
                $this->eventDispatcher->dispatch($event);
            }
        );
    }

    /**
     * @return array[]
     */
    public function handleTestStepFailedEventDataProvider(): array
    {
        return [
            'step failed' => [
                'document' => new Document('{ type: step, status: failed }'),
                'expectedState' => Test::STATE_FAILED,
            ],
        ];
    }

    private function doTestExecuteDocumentReceivedEventDrivenTest(
        Document $document,
        string $expectedState,
        callable $execute
    ): void {
        self::assertSame(Test::STATE_AWAITING, $this->test->getState());

        $event = new TestStepFailedEvent($this->test, $document);
        $execute($event);

        self::assertSame($expectedState, $this->test->getState());
    }
}
