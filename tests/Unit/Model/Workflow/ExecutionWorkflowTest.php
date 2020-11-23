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
            'no tests' => [
                'workflow' => new ExecutionWorkflow(0, 0, 0, null),
                'expectedState' => WorkflowInterface::STATE_NOT_READY,
            ],
            'no finished tests, no running tests, has awaiting tests' => [
                'workflow' => new ExecutionWorkflow(0, 0, 1, null),
                'expectedState' => WorkflowInterface::STATE_NOT_STARTED,
            ],
            'no finished tests, has running tests, no awaiting tests' => [
                'workflow' => new ExecutionWorkflow(0, 1, 0, null),
                'expectedState' => WorkflowInterface::STATE_IN_PROGRESS,
            ],
            'no finished tests, has running tests, has awaiting tests' => [
                'workflow' => new ExecutionWorkflow(0, 1, 1, null),
                'expectedState' => WorkflowInterface::STATE_IN_PROGRESS,
            ],
            'has finished tests, no running tests, no awaiting tests' => [
                'workflow' => new ExecutionWorkflow(1, 0, 0, null),
                'expectedState' => WorkflowInterface::STATE_COMPLETE,
            ],
            'has finished tests, no running tests, has awaiting tests' => [
                'workflow' => new ExecutionWorkflow(1, 0, 1, null),
                'expectedState' => WorkflowInterface::STATE_IN_PROGRESS,
            ],
            'has finished tests, has running tests, no awaiting tests' => [
                'workflow' => new ExecutionWorkflow(1, 1, 0, null),
                'expectedState' => WorkflowInterface::STATE_IN_PROGRESS,
            ],
            'has finished tests, has running tests, has awaiting tests' => [
                'workflow' => new ExecutionWorkflow(1, 1, 1, null),
                'expectedState' => WorkflowInterface::STATE_IN_PROGRESS,
            ],
        ];
    }
}
