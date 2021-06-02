<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Event\JobReadyEvent;
use App\Event\SourceCompilation\PassedEvent;
use App\Message\CompileSourceMessage;
use App\Message\TimeoutCheckMessage;
use App\MessageDispatcher\SendCallbackMessageDispatcher;
use App\Services\CompilationWorkflowHandler;
use App\Services\ExecutionWorkflowHandler;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\MockSuiteManifest;
use App\Tests\Model\EnvironmentSetup;
use App\Tests\Model\SourceSetup;
use App\Tests\Model\TestSetup;
use App\Tests\Services\Asserter\MessengerAsserter;
use App\Tests\Services\EnvironmentFactory;
use App\Tests\Services\EventListenerRemover;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\EventDispatcher\Event;

class CompilationWorkflowHandlerTest extends AbstractBaseFunctionalTest
{
    private CompilationWorkflowHandler $handler;
    private EventDispatcherInterface $eventDispatcher;
    private MessengerAsserter $messengerAsserter;
    private EnvironmentFactory $environmentFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $compilationWorkflowHandler = self::$container->get(CompilationWorkflowHandler::class);
        \assert($compilationWorkflowHandler instanceof CompilationWorkflowHandler);
        $this->handler = $compilationWorkflowHandler;

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
                JobReadyEvent::class => ['dispatchForEvent'],
                PassedEvent::class => ['dispatchForEvent'],
            ],
            ExecutionWorkflowHandler::class => [
                PassedEvent::class => ['dispatchExecutionStartedEvent'],
            ],
        ]);
    }

    /**
     * @dataProvider dispatchNextCompileSourceMessageNoMessageDispatchedDataProvider
     */
    public function testDispatchNextCompileSourceMessageNoMessageDispatched(EnvironmentSetup $setup): void
    {
        $this->environmentFactory->create($setup);

        $this->handler->dispatchNextCompileSourceMessage();

        $this->messengerAsserter->assertQueueIsEmpty();
    }

    /**
     * @return array[]
     */
    public function dispatchNextCompileSourceMessageNoMessageDispatchedDataProvider(): array
    {
        return [
            'no sources' => [
                'setup' => new EnvironmentSetup(),
            ],
            'no non-compiled sources' => [
                'setup' => (new EnvironmentSetup())
                    ->withTestSetups([
                        (new TestSetup())->withSource('/app/source/Test/test1.yml')
                    ]),
            ],
        ];
    }

    /**
     * @dataProvider dispatchNextCompileSourceMessageMessageDispatchedDataProvider
     */
    public function testDispatchNextCompileSourceMessageMessageDispatched(
        EnvironmentSetup $setup,
        CompileSourceMessage $expectedQueuedMessage
    ): void {
        $this->environmentFactory->create($setup);

        $this->handler->dispatchNextCompileSourceMessage();

        $this->messengerAsserter->assertQueueCount(1);
        $this->messengerAsserter->assertMessageAtPositionEquals(0, $expectedQueuedMessage);
    }

    /**
     * @return array[]
     */
    public function dispatchNextCompileSourceMessageMessageDispatchedDataProvider(): array
    {
        return [
            'no sources compiled' => [
                'setup' => (new EnvironmentSetup())
                    ->withSourceSetups([
                        (new SourceSetup())->withPath('Test/test1.yml'),
                        (new SourceSetup())->withPath('Test/test2.yml'),
                    ]),
                'expectedQueuedMessage' => new CompileSourceMessage('Test/test1.yml'),
            ],
            'all but one sources compiled' => [
                'setup' => (new EnvironmentSetup())
                    ->withSourceSetups([
                        (new SourceSetup())->withPath('Test/test1.yml'),
                        (new SourceSetup())->withPath('Test/test2.yml'),
                    ])
                    ->withTestSetups([
                        (new TestSetup())->withSource('Test/test1.yml'),
                    ]),
                'expectedQueuedMessage' => new CompileSourceMessage('Test/test2.yml'),
            ],
        ];
    }

    /**
     * @dataProvider subscribesToEventsDataProvider
     *
     * @param object[] $expectedQueuedMessages
     */
    public function testSubscribesToEvents(Event $event, array $expectedQueuedMessages): void
    {
        $environmentSetup = (new EnvironmentSetup())
            ->withSourceSetups([
                (new SourceSetup())->withPath('Test/test1.yml'),
                (new SourceSetup())->withPath('Test/test2.yml'),
            ])
        ;

        $this->environmentFactory->create($environmentSetup);

        $this->messengerAsserter->assertQueueIsEmpty();

        $this->eventDispatcher->dispatch($event);

        $this->messengerAsserter->assertQueueCount(count($expectedQueuedMessages));
        foreach ($expectedQueuedMessages as $messageIndex => $expectedQueuedMessage) {
            $this->messengerAsserter->assertMessageAtPositionEquals($messageIndex, $expectedQueuedMessage);
        }
    }

    /**
     * @return array[]
     */
    public function subscribesToEventsDataProvider(): array
    {
        return [
            PassedEvent::class => [
                'event' => new PassedEvent(
                    '/app/source/Test/test1.yml',
                    (new MockSuiteManifest())
                        ->withGetTestManifestsCall([])
                        ->getMock()
                ),
                'expectedQueuedMessages' => [
                    new CompileSourceMessage('Test/test1.yml'),
                ],
            ],
            JobReadyEvent::class => [
                'event' => new JobReadyEvent(),
                'expectedQueuedMessages' => [
                    new CompileSourceMessage('Test/test1.yml'),
                    new TimeoutCheckMessage(),
                ],
            ],
        ];
    }
}
