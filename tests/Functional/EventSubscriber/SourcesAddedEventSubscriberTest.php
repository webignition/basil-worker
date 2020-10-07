<?php

declare(strict_types=1);

namespace App\Tests\Functional\EventSubscriber;

use App\Entity\Job;
use App\Event\SourcesAddedEvent;
use App\EventSubscriber\SourcesAddedEventSubscriber;
use App\Message\CompileSource;
use App\Services\JobStore;
use App\Tests\Functional\AbstractBaseFunctionalTest;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\InMemoryTransport;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class SourcesAddedEventSubscriberTest extends AbstractBaseFunctionalTest
{
    private JobStore $jobStore;

    protected function setUp(): void
    {
        parent::setUp();

        $jobStore = self::$container->get(JobStore::class);
        if ($jobStore instanceof JobStore) {
            $jobStore->create('label content', 'http://example.com/callback');
            $this->jobStore = $jobStore;
        }
    }

    public function testGetSubscribedEvents()
    {
        self::assertSame(
            [
                SourcesAddedEvent::NAME => [
                    ['setJobState', 10],
                    ['dispatchCompileSourceMessage', 0]
                ],
            ],
            SourcesAddedEventSubscriber::getSubscribedEvents()
        );
    }

    public function testIntegration()
    {
        $eventDispatcher = self::$container->get(EventDispatcherInterface::class);
        self::assertInstanceOf(EventDispatcherInterface::class, $eventDispatcher);

        $job = $this->jobStore->getJob();
        $job->setSources([
            'Test/test1.yml',
        ]);
        $this->jobStore->store();

        $eventDispatcher->dispatch(new SourcesAddedEvent(), SourcesAddedEvent::NAME);

        $expectedQueuedMessage = new CompileSource('Test/test1.yml');

        self::assertSame(Job::STATE_COMPILATION_RUNNING, $job->getState());

        $messengerTransport = self::$container->get('messenger.transport.async');
        self::assertInstanceOf(InMemoryTransport::class, $messengerTransport);

        $queue = $messengerTransport->get();
        self::assertCount(1, $queue);
        self::assertIsArray($queue);

        /** @var Envelope $envelope */
        $envelope = $queue[0] ?? null;

        self::assertEquals($expectedQueuedMessage, $envelope->getMessage());
    }
}
