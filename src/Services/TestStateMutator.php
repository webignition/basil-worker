<?php

declare(strict_types=1);

namespace App\Services;

use App\Event\TestStepFailedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Test;

class TestStateMutator implements EventSubscriberInterface
{
    public function __construct(
        private EntityPersister $entityPersister
    ) {
    }

    /**
     * @return array<mixed>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            TestStepFailedEvent::class => [
                ['setFailedFromTestStepFailedEvent', 50],
            ],
        ];
    }

    public function setFailedFromTestStepFailedEvent(TestStepFailedEvent $event): void
    {
        $this->setFailed($event->getTest());
    }

    public function setRunning(Test $test): void
    {
        $this->set($test, Test::STATE_RUNNING);
    }

    public function setCompleteIfRunning(Test $test): void
    {
        if ($test->hasState(Test::STATE_RUNNING)) {
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
     * @param Test::STATE_* $state
     */
    private function set(Test $test, string $state): void
    {
        $test->setState($state);
        $this->entityPersister->persist($test);
    }
}
