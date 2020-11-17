<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model\Workflow;

use App\Model\Workflow\CallbackWorkflow;
use App\Model\Workflow\WorkflowInterface;
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
            WorkflowInterface::STATE_NOT_STARTED => [
                'workflow' => new CallbackWorkflow(0, 0),
                'expectedState' => WorkflowInterface::STATE_NOT_STARTED,
            ],
            WorkflowInterface::STATE_IN_PROGRESS => [
                'workflow' => new CallbackWorkflow(1, 0),
                'expectedState' => WorkflowInterface::STATE_IN_PROGRESS,
            ],
            WorkflowInterface::STATE_COMPLETE => [
                'workflow' => new CallbackWorkflow(1, 1),
                'expectedState' => WorkflowInterface::STATE_COMPLETE,
            ],
        ];
    }
}
