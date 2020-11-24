<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model;

use App\Model\JobState;
use PHPUnit\Framework\TestCase;

class JobStateTest extends TestCase
{
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
