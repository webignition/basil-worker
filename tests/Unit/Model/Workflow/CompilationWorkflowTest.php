<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model\Workflow;

use App\Model\Workflow\CompilationWorkflow;
use App\Model\Workflow\WorkflowInterface;
use PHPUnit\Framework\TestCase;

class CompilationWorkflowTest extends TestCase
{
    /**
     * @dataProvider getStateDataProvider
     */
    public function testGetState(CompilationWorkflow $workflow, string $expectedState)
    {
        self::assertSame($expectedState, $workflow->getState());
    }

    public function getStateDataProvider(): array
    {
        return [
            WorkflowInterface::STATE_NOT_READY => [
                'workflow' => new CompilationWorkflow([], null),
                'expectedState' => WorkflowInterface::STATE_NOT_READY,
            ],
            WorkflowInterface::STATE_NOT_STARTED => [
                'workflow' => new CompilationWorkflow([], ''),
                'expectedState' => WorkflowInterface::STATE_NOT_STARTED,
            ],
            WorkflowInterface::STATE_IN_PROGRESS => [
                'workflow' => new CompilationWorkflow([''], ''),
                'expectedState' => WorkflowInterface::STATE_IN_PROGRESS,
            ],
            WorkflowInterface::STATE_COMPLETE => [
                'workflow' => new CompilationWorkflow([''], null),
                'expectedState' => WorkflowInterface::STATE_COMPLETE,
            ],
        ];
    }
}
