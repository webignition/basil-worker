<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\TestFailedEvent;
use App\Repository\TestRepository;
use App\Services\TestStateMutator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TestFailedEventSubscriber implements EventSubscriberInterface
{
    private TestStateMutator $testStateMutator;
    private TestRepository $testRepository;

    public function __construct(
        TestStateMutator $testStateMutator,
        TestRepository $testRepository
    ) {
        $this->testStateMutator = $testStateMutator;
        $this->testRepository = $testRepository;
    }

    public static function getSubscribedEvents()
    {
        return [
            TestFailedEvent::class => [
                ['setTestStateToFailed', 0],
                ['cancelAwaitingTests', 0],
            ],
        ];
    }

    public function setTestStateToFailed(TestFailedEvent $event): void
    {
        $this->testStateMutator->setFailed($event->getTest());
    }

    public function cancelAwaitingTests(): void
    {
        $awaitingTests = $this->testRepository->findAllAwaiting();
        foreach ($awaitingTests as $test) {
            $this->testStateMutator->setCancelled($test);
        }
    }
}
