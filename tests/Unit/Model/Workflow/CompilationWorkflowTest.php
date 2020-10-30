<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model\Workflow;

use App\Model\Workflow\CompilationWorkflow;
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
            CompilationWorkflow::STATE_NOT_READY => [
                'workflow' => new CompilationWorkflow([], null),
                'expectedState' => CompilationWorkflow::STATE_NOT_READY,
            ],
            CompilationWorkflow::STATE_NOT_STARTED => [
                'workflow' => new CompilationWorkflow([], ''),
                'expectedState' => CompilationWorkflow::STATE_NOT_STARTED,
            ],
            CompilationWorkflow::STATE_IN_PROGRESS => [
                'workflow' => new CompilationWorkflow([''], ''),
                'expectedState' => CompilationWorkflow::STATE_IN_PROGRESS,
            ],
            CompilationWorkflow::STATE_COMPLETE => [
                'workflow' => new CompilationWorkflow([''], null),
                'expectedState' => CompilationWorkflow::STATE_COMPLETE,
            ],
        ];
    }
}
