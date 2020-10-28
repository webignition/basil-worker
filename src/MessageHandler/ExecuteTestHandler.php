<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Job;
use App\Entity\Test;
use App\Message\ExecuteTest;
use App\Services\JobStateMutator;
use App\Services\JobStore;
use App\Services\TestExecutor;
use App\Services\TestStore;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class ExecuteTestHandler implements MessageHandlerInterface
{
    private JobStore $jobStore;
    private JobStateMutator $jobStateMutator;
    private TestExecutor $testExecutor;
    private TestStore $testStore;

    public function __construct(
        JobStore $jobStore,
        JobStateMutator $jobStateMutator,
        TestStore $testStore,
        TestExecutor $testExecutor
    ) {
        $this->jobStore = $jobStore;
        $this->jobStateMutator = $jobStateMutator;
        $this->testStore = $testStore;
        $this->testExecutor = $testExecutor;
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

        $test = $this->testStore->find($message->getTestId());
        if (null === $test) {
            return;
        }

        if (Test::STATE_AWAITING !== $test->getState()) {
            return;
        }

        $test->setState(Test::STATE_RUNNING);
        $this->testStore->store($test);

        $this->testExecutor->execute($test);

        // @todo: execute next test #225
    }
}
