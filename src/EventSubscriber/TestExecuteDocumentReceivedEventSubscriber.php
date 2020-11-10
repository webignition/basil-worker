<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\TestExecuteDocumentReceivedEvent;
use App\Event\TestFailedEvent;
use App\Model\Document\Step;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TestExecuteDocumentReceivedEventSubscriber implements EventSubscriberInterface
{
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public static function getSubscribedEvents()
    {
        return [
            TestExecuteDocumentReceivedEvent::class => [
                ['dispatchTestFailedEventIfStepFailed', 10],
            ],
        ];
    }

    public function dispatchTestFailedEventIfStepFailed(TestExecuteDocumentReceivedEvent $event): void
    {
        $this->executeIfStepFailed($event, function (TestExecuteDocumentReceivedEvent $event) {
            $this->eventDispatcher->dispatch(new TestFailedEvent($event->getTest()));
        });
    }

    private function executeIfStepFailed(TestExecuteDocumentReceivedEvent $event, callable $callback): void
    {
        $document = $event->getDocument();

        $step = new Step($document);
        if ($step->isStep() && $step->statusIsFailed()) {
            $callback($event);
        }
    }
}
