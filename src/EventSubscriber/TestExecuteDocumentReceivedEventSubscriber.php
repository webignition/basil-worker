<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\TestExecuteDocumentReceivedEvent;
use App\Event\TestFailedEvent;
use App\Message\SendCallback;
use App\Model\Document\Step;
use App\Services\ExecuteDocumentReceivedCallbackFactory;
use App\Services\JobStateMutator;
use App\Services\TestStateMutator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class TestExecuteDocumentReceivedEventSubscriber implements EventSubscriberInterface
{
    private MessageBusInterface $messageBus;
    private ExecuteDocumentReceivedCallbackFactory $callbackFactory;
    private JobStateMutator $jobStateMutator;
    private TestStateMutator $testStateMutator;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        MessageBusInterface $messageBus,
        ExecuteDocumentReceivedCallbackFactory $callbackFactory,
        JobStateMutator $jobStateMutator,
        TestStateMutator $testStateMutator,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->messageBus = $messageBus;
        $this->callbackFactory = $callbackFactory;
        $this->jobStateMutator = $jobStateMutator;
        $this->testStateMutator = $testStateMutator;
        $this->eventDispatcher = $eventDispatcher;
    }

    public static function getSubscribedEvents()
    {
        return [
            TestExecuteDocumentReceivedEvent::class => [
                ['dispatchTestFailedEventIfStepFailed', 10],
                ['dispatchSendCallbackMessage', 0],
            ],
        ];
    }

    public function dispatchTestFailedEventIfStepFailed(TestExecuteDocumentReceivedEvent $event): void
    {
        $this->executeIfStepFailed($event, function (TestExecuteDocumentReceivedEvent $event) {
            $this->eventDispatcher->dispatch(new TestFailedEvent($event->getTest()));
        });
    }

    public function dispatchSendCallbackMessage(TestExecuteDocumentReceivedEvent $event): void
    {
        $message = new SendCallback(
            $this->callbackFactory->create($event->getDocument())
        );

        $this->messageBus->dispatch($message);
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
