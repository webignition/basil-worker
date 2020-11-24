<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model;

use App\Model\CallbackState;
use PHPUnit\Framework\TestCase;

class CallbackStateTest extends TestCase
{
    /**
     * @dataProvider isFinishedDataProvider
     */
    public function testIsFinished(CallbackState $state, bool $expectedIsFinished)
    {
        self::assertSame($expectedIsFinished, $state->isFinished());
    }

    public function isFinishedDataProvider(): array
    {
        return [
            CallbackState::STATE_AWAITING => [
                'state' => new CallbackState(CallbackState::STATE_AWAITING),
                'expectedIsFinished' => false,
            ],
            CallbackState::STATE_RUNNING => [
                'state' => new CallbackState(CallbackState::STATE_RUNNING),
                'expectedIsFinished' => false,
            ],
            CallbackState::STATE_COMPLETE => [
                'state' => new CallbackState(CallbackState::STATE_COMPLETE),
                'expectedIsFinished' => true,
            ],
        ];
    }
}
