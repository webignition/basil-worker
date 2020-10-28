<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Test;
use App\Message\ExecuteTest;
use Symfony\Component\Messenger\MessageBusInterface;

class ExecutionWorkflowHandler
{
    private TestStore $testStore;
    private MessageBusInterface $messageBus;

    public function __construct(TestStore $testStore, MessageBusInterface $messageBus)
    {
        $this->testStore = $testStore;
        $this->messageBus = $messageBus;
    }

    public function dispatchNextExecuteTestMessage(): void
    {
        $nextAwaitingTest = $this->testStore->findNextAwaiting();

        if ($nextAwaitingTest instanceof Test) {
            $testId = $nextAwaitingTest->getId();

            if (is_int($testId)) {
                $message = new ExecuteTest($testId);
                $this->messageBus->dispatch($message);
            }
        }
    }
}
