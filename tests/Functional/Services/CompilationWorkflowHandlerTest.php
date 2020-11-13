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
use App\Tests\Services\TestTestFactory;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\InMemoryTransport;
use Symfony\Contracts\EventDispatcher\Event;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class CompilationWorkflowHandlerTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private CompilationWorkflowHandler $handler;
    private JobStore $jobStore;
    private InMemoryTransport $messengerTransport;
    private TestTestFactory $testFactory;
    private EventDispatcherInterface $eventDispatcher;

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

        self::assertCount(0, $this->messengerTransport->get());
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

        $queue = $this->messengerTransport->get();
        self::assertCount(1, $queue);
        self::assertIsArray($queue);

        $envelope = $queue[0] ?? null;
        self::assertInstanceOf(Envelope::class, $envelope);
        self::assertEquals($expectedQueuedMessage, $envelope->getMessage());
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

        self::assertCount(0, $this->messengerTransport->get());

        $this->eventDispatcher->dispatch($event);

        self::assertCount(1, $this->messengerTransport->get());
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
