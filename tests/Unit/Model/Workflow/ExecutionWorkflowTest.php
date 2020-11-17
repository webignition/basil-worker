<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model\Workflow;

use App\Model\Workflow\ExecutionWorkflow;
use App\Model\Workflow\WorkflowInterface;
use PHPUnit\Framework\TestCase;

class ExecutionWorkflowTest extends TestCase
{
    /**
     * @dataProvider getStateDataProvider
     */
    public function testGetState(ExecutionWorkflow $workflow, string $expectedState)
    {
        self::assertSame($expectedState, $workflow->getState());
    }

    public function getStateDataProvider(): array
    {
        return [
            WorkflowInterface::STATE_NOT_READY => [
                'workflow' => new ExecutionWorkflow(WorkflowInterface::STATE_IN_PROGRESS, 0, 0, null),
                'expectedState' => WorkflowInterface::STATE_NOT_READY,
            ],
            WorkflowInterface::STATE_NOT_STARTED => [
                'workflow' => new ExecutionWorkflow(WorkflowInterface::STATE_COMPLETE, 3, 3, null),
                'expectedState' => WorkflowInterface::STATE_NOT_STARTED,
            ],
            WorkflowInterface::STATE_IN_PROGRESS => [
                'workflow' => new ExecutionWorkflow(WorkflowInterface::STATE_COMPLETE, 3, 2, null),
                'expectedState' => WorkflowInterface::STATE_IN_PROGRESS,
            ],
            WorkflowInterface::STATE_COMPLETE => [
                'workflow' => new ExecutionWorkflow(WorkflowInterface::STATE_COMPLETE, 3, 0, null),
                'expectedState' => WorkflowInterface::STATE_COMPLETE,
            ],
        ];
    }
}
