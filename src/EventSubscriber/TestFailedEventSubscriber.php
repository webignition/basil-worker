<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\JobCancelledEvent;
use App\Event\TestFailedEvent;
use App\Repository\TestRepository;
use App\Services\JobStore;
use App\Services\TestStateMutator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TestFailedEventSubscriber implements EventSubscriberInterface
{
    private TestStateMutator $testStateMutator;
    private JobStore $jobStore;
    private TestRepository $testRepository;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        TestStateMutator $testStateMutator,
        JobStore $jobStore,
        TestRepository $testRepository,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->testStateMutator = $testStateMutator;
        $this->jobStore = $jobStore;
        $this->testRepository = $testRepository;
        $this->eventDispatcher = $eventDispatcher;
    }

    public static function getSubscribedEvents()
    {
        return [
            TestFailedEvent::class => [
                ['setTestStateToFailed', 0],
                ['setJobStateToCancelled', 0],
                ['cancelAwaitingTests', 0],
            ],
        ];
    }

    public function setTestStateToFailed(TestFailedEvent $event): void
    {
        $this->testStateMutator->setFailed($event->getTest());
    }

    public function setJobStateToCancelled(): void
    {
        $job = $this->jobStore->getJob();

        if (false === $job->isFinished()) {
            $this->eventDispatcher->dispatch(new JobCancelledEvent());
        }
    }

    public function cancelAwaitingTests(): void
    {
        $awaitingTests = $this->testRepository->findAllAwaiting();
        foreach ($awaitingTests as $test) {
            $this->testStateMutator->setCancelled($test);
        }
    }
}
