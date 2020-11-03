<?php

declare(strict_types=1);

namespace App\Tests\Unit\MessageHandler;

use App\Entity\Job;
use App\Entity\Test;
use App\Message\ExecuteTest;
use App\MessageHandler\ExecuteTestHandler;
use App\Services\JobStateMutator;
use App\Services\JobStore;
use App\Services\TestStateMutator;
use App\Services\TestStore;
use App\Tests\Mock\Entity\MockJob;
use App\Tests\Mock\Entity\MockTest;
use App\Tests\Mock\Services\MockJobStateMutator;
use App\Tests\Mock\Services\MockJobStore;
use App\Tests\Mock\Services\MockTestExecutor;
use App\Tests\Mock\Services\MockTestStore;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ExecuteTestHandlerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @dataProvider invokeNoExecutionDataProvider
     */
    public function testInvokeNoExecution(
        JobStore $jobStore,
        JobStateMutator $jobStateMutator,
        TestStore $testStore,
        ExecuteTest $message
    ) {
        $testExecutor = (new MockTestExecutor())
            ->withoutExecuteCall()
            ->getMock();

        $handler = new ExecuteTestHandler(
            $jobStore,
            $jobStateMutator,
            $testStore,
            $testExecutor,
            \Mockery::mock(EventDispatcherInterface::class),
            \Mockery::mock(TestStateMutator::class)
        );

        $handler($message);
    }

    public function invokeNoExecutionDataProvider(): array
    {
        $jobInWrongState = (new MockJob())
            ->withGetStateCall(Job::STATE_COMPILATION_AWAITING)
            ->getMock();

        $jobInCorrectState = (new MockJob())
            ->withGetStateCall(Job::STATE_EXECUTION_RUNNING)
            ->getMock();

        $testInWrongState = (new MockTest())
            ->withGetStateCall(Test::STATE_FAILED)
            ->getMock();

        return [
            'no job' => [
                'jobStore' => (new MockJobStore())
                    ->withHasJobCall(false)
                    ->getMock(),
                'jobStateMutator' => (new MockJobStateMutator())
                    ->getMock(),
                'testStore' => (new MockTestStore())
                    ->withoutFindCall()
                    ->getMock(),
                'message' => new ExecuteTest(1),
            ],
            'job in wrong state' => [
                'jobStore' => (new MockJobStore())
                    ->withHasJobCall(true)
                    ->withGetJobCall($jobInWrongState)
                    ->getMock(),
                'jobStateMutator' => (new MockJobStateMutator())
                    ->getMock(),
                'testStore' => (new MockTestStore())
                    ->withoutFindCall()
                    ->getMock(),
                'message' => new ExecuteTest(1),
            ],
            'no test' => [
                'jobStore' => (new MockJobStore())
                    ->withHasJobCall(true)
                    ->withGetJobCall($jobInCorrectState)
                    ->getMock(),
                'jobStateMutator' => (new MockJobStateMutator())
                    ->getMock(),
                'testStore' => (new MockTestStore())
                    ->withFindCall(1, null)
                    ->getMock(),
                'message' => new ExecuteTest(1),
            ],
            'test in wrong state' => [
                'jobStore' => (new MockJobStore())
                    ->withHasJobCall(true)
                    ->withGetJobCall($jobInCorrectState)
                    ->getMock(),
                'jobStateMutator' => (new MockJobStateMutator())
                    ->getMock(),
                'testStore' => (new MockTestStore())
                    ->withFindCall(1, $testInWrongState)
                    ->getMock(),
                'message' => new ExecuteTest(1),
            ],
        ];
    }
}
