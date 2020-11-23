<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model;

use App\Model\JobState;
use PHPUnit\Framework\TestCase;

class JobStateTest extends TestCase
{
    /**
     * @dataProvider isRunningDataProvider
     */
    public function testIsRunning(JobState $jobState, bool $expectedIsRunning)
    {
        self::assertSame($expectedIsRunning, $jobState->isRunning());
    }

    public function isRunningDataProvider(): array
    {
        return [
            JobState::STATE_COMPILATION_AWAITING => [
                'jobState' => new JobState(JobState::STATE_COMPILATION_AWAITING),
                'expectedIsRunning' => false,
            ],
            JobState::STATE_COMPILATION_RUNNING => [
                'jobState' => new JobState(JobState::STATE_COMPILATION_RUNNING),
                'expectedIsRunning' => true,
            ],
            JobState::STATE_COMPILATION_FAILED => [
                'jobState' => new JobState(JobState::STATE_COMPILATION_FAILED),
                'expectedIsRunning' => false,
            ],
            JobState::STATE_EXECUTION_AWAITING => [
                'jobState' => new JobState(JobState::STATE_EXECUTION_AWAITING),
                'expectedIsRunning' => false,
            ],
            JobState::STATE_EXECUTION_RUNNING => [
                'jobState' => new JobState(JobState::STATE_EXECUTION_RUNNING),
                'expectedIsRunning' => true,
            ],
            JobState::STATE_EXECUTION_FAILED => [
                'jobState' => new JobState(JobState::STATE_EXECUTION_FAILED),
                'expectedIsRunning' => false,
            ],
            JobState::STATE_EXECUTION_COMPLETE => [
                'jobState' => new JobState(JobState::STATE_EXECUTION_COMPLETE),
                'expectedIsRunning' => false,
            ],
            JobState::STATE_EXECUTION_CANCELLED => [
                'jobState' => new JobState(JobState::STATE_EXECUTION_CANCELLED),
                'expectedIsRunning' => false,
            ],
        ];
    }

    /**
     * @dataProvider isFinishedDataProvider
     */
    public function testIsFinished(JobState $jobState, bool $expectedIsFinished)
    {
        self::assertSame($expectedIsFinished, $jobState->isFinished());
    }

    public function isFinishedDataProvider(): array
    {
        return [
            JobState::STATE_COMPILATION_AWAITING => [
                'jobState' => new JobState(JobState::STATE_COMPILATION_AWAITING),
                'expectedIsFinished' => false,
            ],
            JobState::STATE_COMPILATION_RUNNING => [
                'jobState' => new JobState(JobState::STATE_COMPILATION_RUNNING),
                'expectedIsFinished' => false,
            ],
            JobState::STATE_COMPILATION_FAILED => [
                'jobState' => new JobState(JobState::STATE_COMPILATION_FAILED),
                'expectedIsFinished' => true,
            ],
            JobState::STATE_EXECUTION_AWAITING => [
                'jobState' => new JobState(JobState::STATE_EXECUTION_AWAITING),
                'expectedIsFinished' => false,
            ],
            JobState::STATE_EXECUTION_RUNNING => [
                'jobState' => new JobState(JobState::STATE_EXECUTION_RUNNING),
                'expectedIsFinished' => false,
            ],
            JobState::STATE_EXECUTION_FAILED => [
                'jobState' => new JobState(JobState::STATE_EXECUTION_FAILED),
                'expectedIsFinished' => true,
            ],
            JobState::STATE_EXECUTION_COMPLETE => [
                'jobState' => new JobState(JobState::STATE_EXECUTION_COMPLETE),
                'expectedIsFinished' => true,
            ],
            JobState::STATE_EXECUTION_CANCELLED => [
                'jobState' => new JobState(JobState::STATE_EXECUTION_CANCELLED),
                'expectedIsFinished' => true,
            ],
        ];
    }

    /**
     * @dataProvider toStringDataProvider
     */
    public function testToString(JobState $jobState, string $expectedString)
    {
        self::assertSame($expectedString, (string) $jobState);
    }

    public function toStringDataProvider(): array
    {
        return [
            JobState::STATE_COMPILATION_AWAITING => [
                'jobState' => new JobState(JobState::STATE_COMPILATION_AWAITING),
                'expectedString' => JobState::STATE_COMPILATION_AWAITING,
            ],
            JobState::STATE_COMPILATION_RUNNING => [
                'jobState' => new JobState(JobState::STATE_COMPILATION_RUNNING),
                'expectedString' => JobState::STATE_COMPILATION_RUNNING,
            ],
            JobState::STATE_COMPILATION_FAILED => [
                'jobState' => new JobState(JobState::STATE_COMPILATION_FAILED),
                'expectedString' => JobState::STATE_COMPILATION_FAILED,
            ],
            JobState::STATE_EXECUTION_AWAITING => [
                'jobState' => new JobState(JobState::STATE_EXECUTION_AWAITING),
                'expectedString' => JobState::STATE_EXECUTION_AWAITING,
            ],
            JobState::STATE_EXECUTION_RUNNING => [
                'jobState' => new JobState(JobState::STATE_EXECUTION_RUNNING),
                'expectedString' => JobState::STATE_EXECUTION_RUNNING,
            ],
            JobState::STATE_EXECUTION_FAILED => [
                'jobState' => new JobState(JobState::STATE_EXECUTION_FAILED),
                'expectedString' => JobState::STATE_EXECUTION_FAILED,
            ],
            JobState::STATE_EXECUTION_COMPLETE => [
                'jobState' => new JobState(JobState::STATE_EXECUTION_COMPLETE),
                'expectedString' => JobState::STATE_EXECUTION_COMPLETE,
            ],
            JobState::STATE_EXECUTION_CANCELLED => [
                'jobState' => new JobState(JobState::STATE_EXECUTION_CANCELLED),
                'expectedString' => JobState::STATE_EXECUTION_CANCELLED,
            ],
        ];
    }
}
