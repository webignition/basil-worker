<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model\Workflow;

use App\Model\Workflow\CallbackWorkflow;
use App\Model\Workflow\ExecutionWorkflow;
use PHPUnit\Framework\TestCase;

class CallbackWorkflowTest extends TestCase
{
    /**
     * @dataProvider getStateDataProvider
     */
    public function testGetState(CallbackWorkflow $workflow, string $expectedState)
    {
        self::assertSame($expectedState, $workflow->getState());
    }

    public function getStateDataProvider(): array
    {
        return [
            CallbackWorkflow::STATE_NOT_STARTED => [
                'workflow' => new CallbackWorkflow(0, 0),
                'expectedState' => ExecutionWorkflow::STATE_NOT_STARTED,
            ],
            CallbackWorkflow::STATE_IN_PROGRESS => [
                'workflow' => new CallbackWorkflow(1, 0),
                'expectedState' => ExecutionWorkflow::STATE_IN_PROGRESS,
            ],
            CallbackWorkflow::STATE_COMPLETE => [
                'workflow' => new CallbackWorkflow(1, 1),
                'expectedState' => ExecutionWorkflow::STATE_COMPLETE,
            ],
        ];
    }
}
