<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\TestConfiguration;
use App\Event\SourceCompile\SourceCompileSuccessEvent;
use App\Event\SourcesAddedEvent;
use App\Message\CompileSource;
use App\Services\CompilationWorkflowHandler;
use App\Services\JobStore;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\MockSuiteManifest;
use App\Tests\Services\Asserter\MessengerAsserter;
use App\Tests\Services\TestTestFactory;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\EventDispatcher\Event;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class CompilationWorkflowHandlerTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private CompilationWorkflowHandler $handler;
    private JobStore $jobStore;
    private TestTestFactory $testFactory;
    private EventDispatcherInterface $eventDispatcher;
    private MessengerAsserter $messengerAsserter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();

        $this->jobStore->create('label content', 'http://example.com/callback');
    }

    /**
     * @dataProvider dispatchNextCompileSourceMessageNoMessageDispatchedDataProvider
     */
    public function testDispatchNextCompileSourceMessageNoMessageDispatched(callable $initializer)
    {
        $initializer($this->jobStore, $this->testFactory);

        $this->handler->dispatchNextCompileSourceMessage();

        $this->messengerAsserter->assertQueueIsEmpty();
    }

    public function dispatchNextCompileSourceMessageNoMessageDispatchedDataProvider(): array
    {
        return [
            'no sources' => [
                'initializer' => function () {
                },
            ],
            'no non-compiled sources' => [
                'initializer' => function (JobStore $jobStore, TestTestFactory $testFactory) {
                    $job = $jobStore->getJob();
                    $job->setSources([
                        'Test/test1.yml',
                    ]);
                    $jobStore->store($job);

                    $testFactory->create(
                        TestConfiguration::create('chrome', 'http://example.com'),
                        '/app/source/Test/test1.yml',
                        '/app/tests/GeneratedTest1.php',
                        1
                    );
                },
            ],
        ];
    }

    /**
     * @dataProvider dispatchNextCompileSourceMessageMessageDispatchedDataProvider
     */
    public function testDispatchNextCompileSourceMessageMessageDispatched(
        callable $initializer,
        CompileSource $expectedQueuedMessage
    ) {
        $initializer($this->jobStore, $this->testFactory);

        $this->handler->dispatchNextCompileSourceMessage();

        $this->messengerAsserter->assertQueueCount(1);
        $this->messengerAsserter->assertMessageAtPositionEquals(0, $expectedQueuedMessage);
    }

    public function dispatchNextCompileSourceMessageMessageDispatchedDataProvider(): array
    {
        return [
            'no sources compiled' => [
                'initializer' => function (JobStore $jobStore) {
                    $job = $jobStore->getJob();
                    $job->setSources([
                        'Test/test1.yml',
                        'Test/test2.yml',
                    ]);
                    $jobStore->store($job);
                },
                'expectedQueuedMessage' => new CompileSource('Test/test1.yml'),
            ],
            'all but one sources compiled' => [
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
                        '/app/tests/GeneratedTest1.php',
                        1
                    );
                },
                'expectedQueuedMessage' => new CompileSource('Test/test2.yml'),
            ],
        ];
    }

    /**
     * @dataProvider subscribesToEventsDataProvider
     */
    public function testSubscribesToEvents(Event $event)
    {
        $job = $this->jobStore->getJob();
        $job->setSources([
            'Test/test1.yml',
            'Test/test2.yml',
        ]);
        $this->jobStore->store($job);

        $this->messengerAsserter->assertQueueIsEmpty();

        $this->eventDispatcher->dispatch($event);

        $this->messengerAsserter->assertQueueCount(1);
    }

    public function subscribesToEventsDataProvider(): array
    {
        return [
            SourceCompileSuccessEvent::class => [
                'event' => new SourceCompileSuccessEvent(
                    '/app/source/Test/test1.yml',
                    (new MockSuiteManifest())
                        ->withGetTestManifestsCall([])
                        ->getMock()
                ),
            ],
            SourcesAddedEvent::class => [
                'event' => new SourcesAddedEvent(),
            ],
        ];
    }
}
