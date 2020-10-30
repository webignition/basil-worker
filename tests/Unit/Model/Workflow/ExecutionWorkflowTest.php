<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model\Workflow;

use App\Model\Workflow\CompilationWorkflow;
use App\Model\Workflow\ExecutionWorkflow;
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
            ExecutionWorkflow::STATE_NOT_READY => [
                'workflow' => new ExecutionWorkflow(CompilationWorkflow::STATE_IN_PROGRESS, 0, 0, null),
                'expectedState' => ExecutionWorkflow::STATE_NOT_READY,
            ],
            ExecutionWorkflow::STATE_NOT_STARTED => [
                'workflow' => new ExecutionWorkflow(CompilationWorkflow::STATE_COMPLETE, 3, 3, null),
                'expectedState' => ExecutionWorkflow::STATE_NOT_STARTED,
            ],
            ExecutionWorkflow::STATE_IN_PROGRESS => [
                'workflow' => new ExecutionWorkflow(CompilationWorkflow::STATE_COMPLETE, 3, 2, null),
                'expectedState' => ExecutionWorkflow::STATE_IN_PROGRESS,
            ],
            ExecutionWorkflow::STATE_COMPLETE => [
                'workflow' => new ExecutionWorkflow(CompilationWorkflow::STATE_COMPLETE, 3, 0, null),
                'expectedState' => ExecutionWorkflow::STATE_COMPLETE,
            ],
        ];
    }
}
