<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Test;
use App\Event\TestExecuteCompleteEvent;
use App\Message\ExecuteTest;
use App\Model\ExecutionState;
use App\Repository\TestRepository;
use App\Services\ExecutionStateFactory;
use App\Services\JobStore;
use App\Services\TestExecutor;
use App\Services\TestStateMutator;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class ExecuteTestHandler implements MessageHandlerInterface
{
    private JobStore $jobStore;
    private TestExecutor $testExecutor;
    private EventDispatcherInterface $eventDispatcher;
    private TestStateMutator $testStateMutator;
    private TestRepository $testRepository;
    private ExecutionStateFactory $executionStateFactory;

    public function __construct(
        JobStore $jobStore,
        TestExecutor $testExecutor,
        EventDispatcherInterface $eventDispatcher,
        TestStateMutator $testStateMutator,
        TestRepository $testRepository,
        ExecutionStateFactory $executionStateFactory
    ) {
        $this->jobStore = $jobStore;
        $this->testExecutor = $testExecutor;
        $this->eventDispatcher = $eventDispatcher;
        $this->testStateMutator = $testStateMutator;
        $this->testRepository = $testRepository;
        $this->executionStateFactory = $executionStateFactory;
    }

    public function __invoke(ExecuteTest $message): void
    {
        if (false === $this->jobStore->hasJob()) {
            return;
        }

        if ($this->executionStateFactory->is(...ExecutionState::FINISHED_STATES)) {
            return;
        }

        $test = $this->testRepository->find($message->getTestId());
        if (null === $test) {
            return;
        }

        if (Test::STATE_AWAITING !== $test->getState()) {
            return;
        }

        $this->testStateMutator->setRunning($test);
        $this->testExecutor->execute($test);
        $this->testStateMutator->setComplete($test);

        $this->eventDispatcher->dispatch(new TestExecuteCompleteEvent($test));
    }
}
