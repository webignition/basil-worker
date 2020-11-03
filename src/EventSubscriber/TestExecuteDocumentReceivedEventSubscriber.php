<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Test;
use App\Event\TestExecuteDocumentReceivedEvent;
use App\Message\SendCallback;
use App\Model\Document\Step;
use App\Services\ExecuteDocumentReceivedCallbackFactory;
use App\Services\JobStateMutator;
use App\Services\TestStore;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class TestExecuteDocumentReceivedEventSubscriber implements EventSubscriberInterface
{
    private MessageBusInterface $messageBus;
    private TestStore $testStore;
    private ExecuteDocumentReceivedCallbackFactory $callbackFactory;
    private JobStateMutator $jobStateMutator;

    public function __construct(
        MessageBusInterface $messageBus,
        TestStore $testStore,
        ExecuteDocumentReceivedCallbackFactory $callbackFactory,
        JobStateMutator $jobStateMutator
    ) {
        $this->messageBus = $messageBus;
        $this->testStore = $testStore;
        $this->callbackFactory = $callbackFactory;
        $this->jobStateMutator = $jobStateMutator;
    }

    public static function getSubscribedEvents()
    {
        return [
            TestExecuteDocumentReceivedEvent::NAME => [
                ['setJobStateToCompleteIfStepFailed', 20],
                ['setTestStateToFailedIfStepFailed', 10],
                ['dispatchSendCallbackMessage', 0],
            ],
        ];
    }

    public function setJobStateToCompleteIfStepFailed(TestExecuteDocumentReceivedEvent $event): void
    {
        $this->executeIfStepFailed($event, function () {
            $this->jobStateMutator->setExecutionComplete();
        });
    }

    public function setTestStateToFailedIfStepFailed(TestExecuteDocumentReceivedEvent $event): void
    {
        $this->executeIfStepFailed($event, function (TestExecuteDocumentReceivedEvent $event) {
            $test = $event->getTest();
            $test->setState(Test::STATE_FAILED);
            $this->testStore->store($test);
        });
    }

    public function dispatchSendCallbackMessage(TestExecuteDocumentReceivedEvent $event): void
    {
        $message = new SendCallback(
            $this->callbackFactory->create($event->getDocument())
        );

        $this->messageBus->dispatch($message);
    }

    private function executeIfStepFailed(TestExecuteDocumentReceivedEvent $event, callable $bar): void
    {
        $document = $event->getDocument();

        $step = new Step($document);
        if ($step->isStep() && $step->statusIsFailed()) {
            $bar($event);
        }
    }
}
