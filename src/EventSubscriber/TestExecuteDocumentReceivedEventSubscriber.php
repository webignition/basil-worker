<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Test;
use App\Event\TestExecuteDocumentReceivedEvent;
use App\Message\SendCallback;
use App\Model\Callback\ExecuteDocumentReceived;
use App\Model\Document\Step;
use App\Services\TestStore;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class TestExecuteDocumentReceivedEventSubscriber implements EventSubscriberInterface
{
    private MessageBusInterface $messageBus;
    private TestStore $testStore;

    public function __construct(MessageBusInterface $messageBus, TestStore $testStore)
    {
        $this->messageBus = $messageBus;
        $this->testStore = $testStore;
    }

    public static function getSubscribedEvents()
    {
        return [
            TestExecuteDocumentReceivedEvent::NAME => [
                ['setTestSTateToFailedIfFailed', 10],
                ['dispatchSendCallbackMessage', 0],
            ],
        ];
    }

    public function setTestStateToFailedIfFailed(TestExecuteDocumentReceivedEvent $event): void
    {
        $document = $event->getDocument();

        $step = new Step($document);
        if ($step->isStep() && $step->statusIsFailed()) {
            $test = $event->getTest();
            $test->setState(Test::STATE_FAILED);
            $this->testStore->store($test);
        }
    }

    public function dispatchSendCallbackMessage(TestExecuteDocumentReceivedEvent $event): void
    {
        $callback = new ExecuteDocumentReceived($event->getDocument());
        $message = new SendCallback($callback);

        $this->messageBus->dispatch($message);
    }
}
