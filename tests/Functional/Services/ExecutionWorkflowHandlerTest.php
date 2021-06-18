<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Event\CompilationCompletedEvent;
use App\Event\ExecutionStartedEvent;
use App\Event\SourceCompilation\PassedEvent;
use App\Event\TestPassedEvent;
use App\Message\ExecuteTestMessage;
use App\Message\SendCallbackMessage;
use App\MessageDispatcher\SendCallbackMessageDispatcher;
use App\Services\ApplicationWorkflowHandler;
use App\Services\ExecutionWorkflowHandler;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Model\EnvironmentSetup;
use App\Tests\Model\JobSetup;
use App\Tests\Model\TestSetup;
use App\Tests\Services\Asserter\MessengerAsserter;
use App\Tests\Services\EnvironmentFactory;
use App\Tests\Services\EventListenerRemover;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Test;
use webignition\BasilWorker\PersistenceBundle\Services\Repository\CallbackRepository;
use webignition\YamlDocument\Document;

class ExecutionWorkflowHandlerTest extends AbstractBaseFunctionalTest
{
    private ExecutionWorkflowHandler $handler;
    private EventDispatcherInterface $eventDispatcher;
    private MessengerAsserter $messengerAsserter;
    private EnvironmentFactory $environmentFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $executionWorkflowHandler = self::$container->get(ExecutionWorkflowHandler::class);
        \assert($executionWorkflowHandler instanceof ExecutionWorkflowHandler);
        $this->handler = $executionWorkflowHandler;

        $eventDispatcher = self::$container->get(EventDispatcherInterface::class);
        \assert($eventDispatcher instanceof EventDispatcherInterface);
        $this->eventDispatcher = $eventDispatcher;

        $messengerAsserter = self::$container->get(MessengerAsserter::class);
        \assert($messengerAsserter instanceof MessengerAsserter);
        $this->messengerAsserter = $messengerAsserter;

        $environmentFactory = self::$container->get(EnvironmentFactory::class);
        \assert($environmentFactory instanceof EnvironmentFactory);
        $this->environmentFactory = $environmentFactory;

        $eventListenerRemover = self::$container->get(EventListenerRemover::class);
        \assert($eventListenerRemover instanceof EventListenerRemover);

        $eventListenerRemover->remove([
            SendCallbackMessageDispatcher::class => [
                PassedEvent::class => ['dispatchForEvent'],
                CompilationCompletedEvent::class => ['dispatchForEvent'],
                TestPassedEvent::class => ['dispatchForEvent'],
                ExecutionStartedEvent::class => ['dispatchForEvent'],
            ],
            ApplicationWorkflowHandler::class => [
                TestPassedEvent::class => ['dispatchJobCompletedEvent'],
            ],
        ]);
    }

    public function testDispatchNextExecuteTestMessageNoMessageDispatched(): void
    {
        $this->handler->dispatchNextExecuteTestMessage();
        $this->messengerAsserter->assertQueueIsEmpty();
    }

    /**
     * @dataProvider dispatchNextExecuteTestMessageMessageDispatchedDataProvider
     */
    public function testDispatchNextExecuteTestMessageMessageDispatched(
        EnvironmentSetup $setup,
        int $expectedNextTestIndex
    ): void {
        $this->doCompilationCompleteEventDrivenTest(
            $setup,
            function () {
                $this->handler->dispatchNextExecuteTestMessage();
            },
            $expectedNextTestIndex,
        );
    }

    /**
     * @return array[]
     */
    public function dispatchNextExecuteTestMessageMessageDispatchedDataProvider(): array
    {
        return [
            'two tests, none run' => [
                'setup' => (new EnvironmentSetup())
                    ->withJobSetup(new JobSetup())
                    ->withTestSetups([
                        (new TestSetup())->withSource('/app/source/Test/test1.yml'),
                        (new TestSetup())->withSource('/app/source/Test/test2.yml'),
                    ]),
                'expectedNextTestIndex' => 0,
            ],
            'three tests, first complete' => [
                'setup' => (new EnvironmentSetup())
                    ->withJobSetup(new JobSetup())
                    ->withTestSetups([
                        (new TestSetup())
                            ->withSource('/app/source/Test/test1.yml')
                            ->withState(Test::STATE_COMPLETE),
                        (new TestSetup())->withSource('/app/source/Test/test2.yml'),
                        (new TestSetup())->withSource('/app/source/Test/test3.yml'),
                    ]),
                'expectedNextTestIndex' => 1,
            ],
            'three tests, first, second complete' => [
                'setup' => (new EnvironmentSetup())
                    ->withJobSetup(new JobSetup())
                    ->withTestSetups([
                        (new TestSetup())
                            ->withSource('/app/source/Test/test1.yml')
                            ->withState(Test::STATE_COMPLETE),
                        (new TestSetup())
                            ->withSource('/app/source/Test/test2.yml')
                            ->withState(Test::STATE_COMPLETE),
                        (new TestSetup())->withSource('/app/source/Test/test3.yml'),
                    ]),
                'expectedNextTestIndex' => 2,
            ],
        ];
    }

    public function testSubscribesToCompilationCompletedEvent(): void
    {
        $environmentSetup = (new EnvironmentSetup())
            ->withJobSetup(new JobSetup())
            ->withTestSetups([
                (new TestSetup())
                    ->withSource('/app/source/Test/test1.yml')
                    ->withState(Test::STATE_COMPLETE),
                (new TestSetup())
                    ->withSource('/app/source/Test/test2.yml'),
            ])
        ;

        $this->doCompilationCompleteEventDrivenTest(
            $environmentSetup,
            function () {
                $this->eventDispatcher->dispatch(new CompilationCompletedEvent());
            },
            1,
        );
    }

    /**
     * @dataProvider dispatchNextExecuteTestMessageFromTestPassedEventDataProvider
     */
    public function testDispatchNextExecuteTestMessageFromTestPassedEvent(
        EnvironmentSetup $setup,
        int $eventTestIndex,
        int $expectedQueuedMessageCount,
        ?int $expectedNextTestIndex
    ): void {
        $environment = $this->environmentFactory->create($setup);
        $tests = $environment->getTests();
        $this->messengerAsserter->assertQueueIsEmpty();

        $test = $tests[$eventTestIndex];
        $event = new TestPassedEvent($test, \Mockery::mock(Document::class));

        $this->handler->dispatchNextExecuteTestMessageFromTestPassedEvent($event);

        $this->messengerAsserter->assertQueueCount($expectedQueuedMessageCount);

        if (is_int($expectedNextTestIndex)) {
            $expectedNextTest = $tests[$expectedNextTestIndex] ?? null;
            self::assertInstanceOf(Test::class, $expectedNextTest);

            $this->messengerAsserter->assertMessageAtPositionEquals(
                0,
                new ExecuteTestMessage((int) $expectedNextTest->getId())
            );
        }
    }

    /**
     * @return array[]
     */
    public function dispatchNextExecuteTestMessageFromTestPassedEventDataProvider(): array
    {
        return [
            'single test, not complete' => [
                'setup' => (new EnvironmentSetup())
                    ->withJobSetup(new JobSetup())
                    ->withTestSetups([
                        (new TestSetup())
                            ->withSource('/app/source/Test/test1.yml')
                            ->withState(Test::STATE_FAILED),
                    ]),
                'eventTestIndex' => 0,
                'expectedQueuedMessageCount' => 0,
                'expectedNextTestIndex' => null,
            ],
            'single test, is complete' => [
                'setup' => (new EnvironmentSetup())
                    ->withJobSetup(new JobSetup())
                    ->withTestSetups([
                        (new TestSetup())
                            ->withSource('/app/source/Test/test1.yml')
                            ->withState(Test::STATE_CANCELLED),
                    ]),
                'eventTestIndex' => 0,
                'expectedQueuedMessageCount' => 0,
                'expectedNextTestIndex' => null,
            ],
            'multiple tests, not complete' => [
                'setup' => (new EnvironmentSetup())
                    ->withJobSetup(new JobSetup())
                    ->withTestSetups([
                        (new TestSetup())
                            ->withSource('/app/source/Test/test1.yml')
                            ->withState(Test::STATE_FAILED),
                        (new TestSetup())
                            ->withSource('/app/source/Test/test2.yml')
                            ->withState(Test::STATE_AWAITING),
                    ]),
                'eventTestIndex' => 0,
                'expectedQueuedMessageCount' => 0,
                'expectedNextTestIndex' => null,
            ],
            'multiple tests, complete' => [
                'setup' => (new EnvironmentSetup())
                    ->withJobSetup(new JobSetup())
                    ->withTestSetups([
                        (new TestSetup())
                            ->withSource('/app/source/Test/test1.yml')
                            ->withState(Test::STATE_COMPLETE),
                        (new TestSetup())
                            ->withSource('/app/source/Test/test2.yml')
                            ->withState(Test::STATE_AWAITING),
                    ]),
                'eventTestIndex' => 0,
                'expectedQueuedMessageCount' => 1,
                'expectedNextTestIndex' => 1,
            ],
        ];
    }

    public function testSubscribesToTestPassedEventExecutionNotComplete(): void
    {
        $environmentSetup = (new EnvironmentSetup())
            ->withJobSetup(new JobSetup())
            ->withTestSetups([
                (new TestSetup())
                    ->withSource('/app/source/Test/test1.yml')
                    ->withState(Test::STATE_COMPLETE),
                (new TestSetup())
                    ->withSource('/app/source/Test/test2.yml')
                    ->withState(Test::STATE_AWAITING),
            ])
        ;

        $environment = $this->environmentFactory->create($environmentSetup);
        $tests = $environment->getTests();

        $this->eventDispatcher->dispatch(new TestPassedEvent($tests[0], new Document('')));

        $this->messengerAsserter->assertQueueCount(1);

        $expectedNextTest = $tests[1] ?? null;
        self::assertInstanceOf(Test::class, $expectedNextTest);

        $this->messengerAsserter->assertMessageAtPositionEquals(
            0,
            new ExecuteTestMessage((int) $expectedNextTest->getId())
        );
    }

    public function testSubscribesToTestPassedEventExecutionComplete(): void
    {
        $environmentSetup = (new EnvironmentSetup())
            ->withJobSetup(new JobSetup())
            ->withTestSetups([
                (new TestSetup())
                    ->withSource('/app/source/Test/test1.yml')
                    ->withState(Test::STATE_COMPLETE),
                (new TestSetup())
                    ->withSource('/app/source/Test/test2.yml')
                    ->withState(Test::STATE_COMPLETE),
            ])
        ;

        $environment = $this->environmentFactory->create($environmentSetup);
        $tests = $environment->getTests();

        $this->eventDispatcher->dispatch(new TestPassedEvent($tests[0], new Document('')));

        $this->messengerAsserter->assertQueueCount(1);

        $callbackRespository = self::$container->get(CallbackRepository::class);
        \assert($callbackRespository instanceof CallbackRepository);
        $callbacks = $callbackRespository->findAll();
        $expectedCallback = array_pop($callbacks);

        self::assertInstanceOf(CallbackInterface::class, $expectedCallback);

        $this->messengerAsserter->assertMessageAtPositionEquals(
            0,
            new SendCallbackMessage((int) $expectedCallback->getId())
        );
    }

    private function doCompilationCompleteEventDrivenTest(
        EnvironmentSetup $setup,
        callable $execute,
        int $expectedNextTestIndex
    ): void {
        $this->messengerAsserter->assertQueueIsEmpty();

        $environment = $this->environmentFactory->create($setup);
        $tests = $environment->getTests();

        $execute();

        $this->messengerAsserter->assertQueueCount(1);

        $expectedNextTest = $tests[$expectedNextTestIndex] ?? null;
        self::assertInstanceOf(Test::class, $expectedNextTest);

        $this->messengerAsserter->assertMessageAtPositionEquals(
            0,
            new ExecuteTestMessage((int) $expectedNextTest->getId())
        );
    }
}
