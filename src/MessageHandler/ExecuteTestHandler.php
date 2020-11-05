<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Job;
use App\Entity\Test;
use App\Event\TestExecuteCompleteEvent;
use App\Message\ExecuteTest;
use App\Repository\TestRepository;
use App\Services\JobStateMutator;
use App\Services\JobStore;
use App\Services\TestExecutor;
use App\Services\TestStateMutator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class ExecuteTestHandler implements MessageHandlerInterface
{
    private JobStore $jobStore;
    private JobStateMutator $jobStateMutator;
    private TestExecutor $testExecutor;
    private EventDispatcherInterface $eventDispatcher;
    private TestStateMutator $testStateMutator;
    private TestRepository $testRepository;

    public function __construct(
        JobStore $jobStore,
        JobStateMutator $jobStateMutator,
        TestExecutor $testExecutor,
        EventDispatcherInterface $eventDispatcher,
        TestStateMutator $testStateMutator,
        TestRepository $testRepository
    ) {
        $this->jobStore = $jobStore;
        $this->jobStateMutator = $jobStateMutator;
        $this->testExecutor = $testExecutor;
        $this->eventDispatcher = $eventDispatcher;
        $this->testStateMutator = $testStateMutator;
        $this->testRepository = $testRepository;
    }

    public function __invoke(ExecuteTest $message): void
    {
        if (false === $this->jobStore->hasJob()) {
            return;
        }

        $job = $this->jobStore->getJob();

        if (Job::STATE_EXECUTION_AWAITING === $job->getState()) {
            $this->jobStateMutator->setExecutionRunning();
        }

        if (Job::STATE_EXECUTION_RUNNING !== $job->getState()) {
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
