<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model;

use App\Model\ExecutionState;
use PHPUnit\Framework\TestCase;

class ExecutionStateTest extends TestCase
{
    /**
     * @dataProvider isFinishedDataProvider
     */
    public function testIsFinished(ExecutionState $state, bool $expectedIsFinished)
    {
        self::assertSame($expectedIsFinished, $state->isFinished());
    }

    public function isFinishedDataProvider(): array
    {
        return [
            ExecutionState::STATE_AWAITING => [
                'state' => new ExecutionState(ExecutionState::STATE_AWAITING),
                'expectedIsFinished' => false,
            ],
            ExecutionState::STATE_RUNNING => [
                'state' => new ExecutionState(ExecutionState::STATE_RUNNING),
                'expectedIsFinished' => false,
            ],
            ExecutionState::STATE_COMPLETE => [
                'state' => new ExecutionState(ExecutionState::STATE_COMPLETE),
                'expectedIsFinished' => true,
            ],
            ExecutionState::STATE_CANCELLED => [
                'state' => new ExecutionState(ExecutionState::STATE_CANCELLED),
                'expectedIsFinished' => true,
            ],
        ];
    }
}
