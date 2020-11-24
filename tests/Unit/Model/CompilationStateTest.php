<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model;

use App\Model\CompilationState;
use PHPUnit\Framework\TestCase;

class CompilationStateTest extends TestCase
{
    /**
     * @dataProvider isFinishedDataProvider
     */
    public function testIsFinished(CompilationState $state, bool $expectedIsFinished)
    {
        self::assertSame($expectedIsFinished, $state->isFinished());
    }

    public function isFinishedDataProvider(): array
    {
        return [
            CompilationState::STATE_AWAITING => [
                'state' => new CompilationState(CompilationState::STATE_AWAITING),
                'expectedIsFinished' => false,
            ],
            CompilationState::STATE_RUNNING => [
                'state' => new CompilationState(CompilationState::STATE_RUNNING),
                'expectedIsFinished' => false,
            ],
            CompilationState::STATE_FAILED => [
                'state' => new CompilationState(CompilationState::STATE_FAILED),
                'expectedIsFinished' => true,
            ],
            CompilationState::STATE_COMPLETE => [
                'state' => new CompilationState(CompilationState::STATE_COMPLETE),
                'expectedIsFinished' => true,
            ],
            CompilationState::STATE_UNKNOWN => [
                'state' => new CompilationState(CompilationState::STATE_UNKNOWN),
                'expectedIsFinished' => false,
            ],
        ];
    }
}
