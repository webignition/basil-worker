<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model;

use App\Model\ApplicationState;
use PHPUnit\Framework\TestCase;

class ApplicationStateTest extends TestCase
{
    /**
     * @dataProvider isFinishedDataProvider
     */
    public function testIsFinished(ApplicationState $state, bool $expectedIsFinished)
    {
        self::assertSame($expectedIsFinished, $state->isFinished());
    }

    public function isFinishedDataProvider(): array
    {
        return [
            ApplicationState::STATE_AWAITING_JOB => [
                'state' => new ApplicationState(ApplicationState::STATE_AWAITING_JOB),
                'expectedIsFinished' => false,
            ],
            ApplicationState::STATE_COMPILING => [
                'state' => new ApplicationState(ApplicationState::STATE_COMPILING),
                'expectedIsFinished' => false,
            ],
            ApplicationState::STATE_COMPLETE => [
                'state' => new ApplicationState(ApplicationState::STATE_COMPLETE),
                'expectedIsFinished' => true,
            ],
        ];
    }
}
