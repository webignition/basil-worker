<?php

declare(strict_types=1);

namespace App\Tests\Unit\MessageHandler;

use App\Entity\Test;
use App\Message\ExecuteTest;
use App\MessageHandler\ExecuteTestHandler;
use App\Model\JobState;
use App\Repository\TestRepository;
use App\Services\JobStateFactory;
use App\Services\JobStore;
use App\Services\TestStateMutator;
use App\Tests\Mock\Entity\MockJob;
use App\Tests\Mock\Entity\MockTest;
use App\Tests\Mock\Repository\MockTestRepository;
use App\Tests\Mock\Services\MockJobStateFactory;
use App\Tests\Mock\Services\MockJobStore;
use App\Tests\Mock\Services\MockTestExecutor;
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
        JobStateFactory $jobStateFactory,
        ExecuteTest $message,
        TestRepository $testRepository
    ) {
        $testExecutor = (new MockTestExecutor())
            ->withoutExecuteCall()
            ->getMock();

        $handler = new ExecuteTestHandler(
            $jobStore,
            $testExecutor,
            \Mockery::mock(EventDispatcherInterface::class),
            \Mockery::mock(TestStateMutator::class),
            $testRepository,
            $jobStateFactory
        );

        $handler($message);
    }

    public function invokeNoExecutionDataProvider(): array
    {
        $testInWrongState = (new MockTest())
            ->withGetStateCall(Test::STATE_FAILED)
            ->getMock();

        return [
            'no job' => [
                'jobStore' => (new MockJobStore())
                    ->withHasJobCall(false)
                    ->getMock(),
                'jobStateFactory' => (new MockJobStateFactory())
                    ->getMock(),
                'message' => new ExecuteTest(1),
                'testRepository' => (new MockTestRepository())
                    ->withoutFindCall()
                    ->getMock(),
            ],
            'job in wrong state' => [
                'jobStore' => (new MockJobStore())
                    ->withHasJobCall(true)
                    ->withGetJobCall((new MockJob())->getMock())
                    ->getMock(),
                'jobStateFactory' => (new MockJobStateFactory())
                    ->withCreateCall(new JobState(JobState::STATE_COMPILATION_AWAITING))
                    ->getMock(),
                'message' => new ExecuteTest(1),
                'testRepository' => (new MockTestRepository())
                    ->withoutFindCall()
                    ->getMock(),
            ],
            'no test' => [
                'jobStore' => (new MockJobStore())
                    ->withHasJobCall(true)
                    ->withGetJobCall((new MockJob())->getMock())
                    ->getMock(),
                'jobStateFactory' => (new MockJobStateFactory())
                    ->withCreateCall(new JobState(JobState::STATE_EXECUTION_AWAITING))
                    ->getMock(),
                'message' => new ExecuteTest(1),
                'testRepository' => (new MockTestRepository())
                    ->withFindCall(1, null)
                    ->getMock(),
            ],
            'test in wrong state' => [
                'jobStore' => (new MockJobStore())
                    ->withHasJobCall(true)
                    ->withGetJobCall((new MockJob())->getMock())
                    ->getMock(),
                'jobStateFactory' => (new MockJobStateFactory())
                    ->withCreateCall(new JobState(JobState::STATE_EXECUTION_RUNNING))
                    ->getMock(),
                'message' => new ExecuteTest(1),
                'testRepository' => (new MockTestRepository())
                    ->withFindCall(1, $testInWrongState)
                    ->getMock(),
            ],
        ];
    }
}
