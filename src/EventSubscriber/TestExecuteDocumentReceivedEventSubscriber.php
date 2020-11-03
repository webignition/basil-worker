<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Test;
use App\Event\TestExecuteDocumentReceivedEvent;
use App\Message\SendCallback;
use App\Model\Document\Step;
use App\Services\ExecuteDocumentReceivedCallbackFactory;
use App\Services\TestStore;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class TestExecuteDocumentReceivedEventSubscriber implements EventSubscriberInterface
{
    private MessageBusInterface $messageBus;
    private TestStore $testStore;
    private ExecuteDocumentReceivedCallbackFactory $callbackFactory;

    public function __construct(
        MessageBusInterface $messageBus,
        TestStore $testStore,
        ExecuteDocumentReceivedCallbackFactory $callbackFactory
    ) {
        $this->messageBus = $messageBus;
        $this->testStore = $testStore;
        $this->callbackFactory = $callbackFactory;
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
        $message = new SendCallback(
            $this->callbackFactory->create($event->getDocument())
        );

        $this->messageBus->dispatch($message);
    }
}
