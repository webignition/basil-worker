<?php

declare(strict_types=1);

namespace App\Tests\Functional\EventSubscriber;

use App\Entity\Job;
use App\Event\SourceCompileFailureEvent;
use App\EventSubscriber\SourceCompileFailureEventSubscriber;
use App\Message\SendCallback;
use App\Model\Callback\CompileFailure;
use App\Services\JobStore;
use App\Tests\AbstractBaseFunctionalTest;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Transport\InMemoryTransport;
use webignition\BasilCompilerModels\ErrorOutputInterface;

class SourceCompileFailureEventSubscriberTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;

    private SourceCompileFailureEventSubscriber $eventSubscriber;
    private InMemoryTransport $messengerTransport;
    private Job $job;

    protected function setUp(): void
    {
        parent::setUp();

        $eventSubscriber = self::$container->get(SourceCompileFailureEventSubscriber::class);
        if ($eventSubscriber instanceof SourceCompileFailureEventSubscriber) {
            $this->eventSubscriber = $eventSubscriber;
        }

        $jobStore = self::$container->get(JobStore::class);
        if ($jobStore instanceof JobStore) {
            $this->job = $jobStore->create('label content', 'http://example.com/callback');
        }

        $messengerTransport = self::$container->get('messenger.transport.async');
        if ($messengerTransport instanceof InMemoryTransport) {
            $this->messengerTransport = $messengerTransport;
        }
    }

    public function testSetJobState()
    {
        self::assertSame(Job::STATE_COMPILATION_AWAITING, $this->job->getState());

        $this->eventSubscriber->setJobState();

        $this->assertJobState();
    }

    public function testDispatchSendCallbackMessage()
    {
        $errorOutput = \Mockery::mock(ErrorOutputInterface::class);
        $event = new SourceCompileFailureEvent('Test/test1.yml', $errorOutput);

        $this->eventSubscriber->dispatchSendCallbackMessage($event);

        $this->assertMessageTransportQueue($errorOutput);
    }

    public function testIntegration()
    {
        self::assertSame(Job::STATE_COMPILATION_AWAITING, $this->job->getState());
        self::assertCount(0, $this->messengerTransport->get());

        $errorOutput = \Mockery::mock(ErrorOutputInterface::class);
        $event = new SourceCompileFailureEvent('Test/test1.yml', $errorOutput);

        $eventDispatcher = self::$container->get(EventDispatcherInterface::class);
        if ($eventDispatcher instanceof EventDispatcherInterface) {
            $eventDispatcher->dispatch($event);
        }

        $this->assertJobState();
        $this->assertMessageTransportQueue($errorOutput);
    }

    private function assertJobState(): void
    {
        self::assertSame(Job::STATE_COMPILATION_FAILED, $this->job->getState());
    }

    private function assertMessageTransportQueue(ErrorOutputInterface $errorOutput): void
    {
        $queue = $this->messengerTransport->get();
        self::assertCount(1, $queue);
        self::assertIsArray($queue);

        $expectedCallback = new CompileFailure($errorOutput);
        $expectedQueuedMessage = new SendCallback($expectedCallback);

        self::assertEquals($expectedQueuedMessage, $queue[0]->getMessage());
    }
}
