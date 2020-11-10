<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Test;
use App\Event\TestExecuteDocumentReceivedEvent;
use App\Repository\TestRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TestCanceller implements EventSubscriberInterface
{
    private TestStateMutator $testStateMutator;
    private TestRepository $testRepository;

    public function __construct(TestStateMutator $testStateMutator, TestRepository $testRepository)
    {
        $this->testStateMutator = $testStateMutator;
        $this->testRepository = $testRepository;
    }

    public static function getSubscribedEvents()
    {
        return [
            TestExecuteDocumentReceivedEvent::class => [
                ['cancelAwaitingFromTestExecuteDocumentReceivedEvent', 10],
            ],
        ];
    }

    public function cancelAwaitingFromTestExecuteDocumentReceivedEvent(TestExecuteDocumentReceivedEvent $event): void
    {
        $test = $event->getTest();

        if (Test::STATE_FAILED === $test->getState()) {
            $this->cancelAwaiting();
        }
    }

    public function cancelAwaiting(): void
    {
        $awaitingTests = $this->testRepository->findAllAwaiting();

        foreach ($awaitingTests as $awaitingTest) {
            $this->testStateMutator->setCancelled($awaitingTest);
        }
    }
}
