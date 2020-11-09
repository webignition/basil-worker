<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Job;
use App\Services\JobStateMutator;
use App\Services\JobStore;
use App\Tests\AbstractBaseFunctionalTest;

class JobStateMutatorTest extends AbstractBaseFunctionalTest
{
    private JobStateMutator $jobStateMutator;
    private JobStore $jobStore;
    private Job $job;

    protected function setUp(): void
    {
        parent::setUp();

        $jobStateMutator = self::$container->get(JobStateMutator::class);
        self::assertInstanceOf(JobStateMutator::class, $jobStateMutator);
        if ($jobStateMutator instanceof JobStateMutator) {
            $this->jobStateMutator = $jobStateMutator;
        }

        $jobStore = self::$container->get(JobStore::class);
        self::assertInstanceOf(JobStore::class, $jobStore);
        if ($jobStore instanceof JobStore) {
            $this->job = $jobStore->create(md5('label content'), 'http://example.com/callback');
            $this->jobStore = $jobStore;
        }
    }

    /**
     * @dataProvider setExecutionCancelledDataProvider
     *
     * @param Job::STATE_* $startState
     * @param Job::STATE_* $expectedEndState
     */
    public function testSetExecutionCancelled(string $startState, string $expectedEndState)
    {
        $this->job->setState($startState);
        $this->jobStore->store($this->job);
        self::assertSame($startState, $this->job->getState());

        $this->jobStateMutator->setExecutionCancelled();

        self::assertSame($expectedEndState, $this->job->getState());
    }

    public function setExecutionCancelledDataProvider(): array
    {
        return [
            'state: compilation-awaiting' => [
                'startState' => Job::STATE_COMPILATION_AWAITING,
                'expectedEndState' => Job::STATE_EXECUTION_CANCELLED,
            ],
            'state: compilation-running' => [
                'startState' => Job::STATE_COMPILATION_RUNNING,
                'expectedEndState' => Job::STATE_EXECUTION_CANCELLED,
            ],
            'state: compilation-failed' => [
                'startState' => Job::STATE_COMPILATION_FAILED,
                'expectedEndState' => Job::STATE_COMPILATION_FAILED,
            ],
            'state: execution-awaiting' => [
                'startState' => Job::STATE_EXECUTION_AWAITING,
                'expectedEndState' => Job::STATE_EXECUTION_CANCELLED,
            ],
            'state: execution-running' => [
                'startState' => Job::STATE_EXECUTION_RUNNING,
                'expectedEndState' => Job::STATE_EXECUTION_CANCELLED,
            ],
            'state: execution-failed' => [
                'startState' => Job::STATE_EXECUTION_FAILED,
                'expectedEndState' => Job::STATE_EXECUTION_FAILED,
            ],
            'state: execution-complete' => [
                'startState' => Job::STATE_EXECUTION_COMPLETE,
                'expectedEndState' => Job::STATE_EXECUTION_COMPLETE,
            ],
            'state: execution-cancelled' => [
                'startState' => Job::STATE_EXECUTION_CANCELLED,
                'expectedEndState' => Job::STATE_EXECUTION_CANCELLED,
            ],
        ];
    }
}
