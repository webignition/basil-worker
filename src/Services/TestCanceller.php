<?php

declare(strict_types=1);

namespace App\Services;

use App\Event\JobTimeoutEvent;
use App\Event\TestStepFailedEvent;
use App\Repository\TestRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use App\Entity\Test;

class TestCanceller implements EventSubscriberInterface
{
    public function __construct(
        private TestStateMutator $testStateMutator,
        private TestRepository $testRepository
    ) {
    }

    /**
     * @return array<mixed>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            TestStepFailedEvent::class => [
                ['cancelAwaitingFromTestFailedEvent', 0],
            ],
            JobTimeoutEvent::class => [
                ['cancelUnfinished', 0],
            ],
        ];
    }

    public function cancelAwaitingFromTestFailedEvent(TestStepFailedEvent $event): void
    {
        $this->cancelAwaiting();
    }

    public function cancelUnfinished(): void
    {
        $this->cancelCollection($this->testRepository->findAllUnfinished());
    }

    public function cancelAwaiting(): void
    {
        $this->cancelCollection($this->testRepository->findAllAwaiting());
    }

    /**
     * @param Test[] $tests
     */
    private function cancelCollection(array $tests): void
    {
        foreach ($tests as $test) {
            $this->testStateMutator->setCancelled($test);
        }
    }
}
