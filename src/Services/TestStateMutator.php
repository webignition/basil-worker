<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Test;
use App\Event\TestExecuteCompleteEvent;
use App\Event\TestFailedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TestStateMutator implements EventSubscriberInterface
{
    private TestStore $testStore;

    public function __construct(TestStore $testStore)
    {
        $this->testStore = $testStore;
    }

    public static function getSubscribedEvents()
    {
        return [
            TestFailedEvent::class => [
                ['setFailedFromTestFailedEvent', 0],
            ],
            TestExecuteCompleteEvent::class => [
                ['setCompleteFromTestExecuteCompleteEvent', 100],
            ],
        ];
    }

    public function setFailedFromTestFailedEvent(TestFailedEvent $event): void
    {
        $this->setFailed($event->getTest());
    }

    public function setCompleteFromTestExecuteCompleteEvent(TestExecuteCompleteEvent $event): void
    {
        $this->setComplete($event->getTest());
    }

    public function setRunning(Test $test): void
    {
        $this->set($test, Test::STATE_RUNNING);
    }

    public function setComplete(Test $test): void
    {
        if (Test::STATE_RUNNING === $test->getState()) {
            $this->set($test, Test::STATE_COMPLETE);
        }
    }

    public function setFailed(Test $test): void
    {
        $this->set($test, Test::STATE_FAILED);
    }

    public function setCancelled(Test $test): void
    {
        $this->set($test, Test::STATE_CANCELLED);
    }

    /**
     * @param Test $test
     * @param Test::STATE_* $state
     */
    private function set(Test $test, string $state): void
    {
        $test->setState($state);
        $this->testStore->store($test);
    }
}
