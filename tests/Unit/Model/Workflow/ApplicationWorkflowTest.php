<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model\Workflow;

use App\Model\CompilationState;
use App\Model\ExecutionState;
use App\Model\Workflow\ApplicationWorkflow;
use App\Model\Workflow\WorkflowInterface;
use PHPUnit\Framework\TestCase;

class ApplicationWorkflowTest extends TestCase
{
    /**
     * @dataProvider getStateDataProvider
     */
    public function testGetState(ApplicationWorkflow $workflow, string $expectedState)
    {
        self::assertSame($expectedState, $workflow->getState());
    }

    public function getStateDataProvider(): array
    {
        return [
            'job does not exist' => [
                'workflow' => new ApplicationWorkflow(
                    false,
                    new CompilationState(CompilationState::STATE_AWAITING),
                    new ExecutionState(ExecutionState::STATE_AWAITING)
                ),
                'expectedState' => WorkflowInterface::STATE_NOT_STARTED,
            ],
            'job state: compilation-awaiting' => [
                'workflow' => new ApplicationWorkflow(
                    false,
                    new CompilationState(CompilationState::STATE_AWAITING),
                    new ExecutionState(ExecutionState::STATE_AWAITING)
                ),
                'expectedState' => WorkflowInterface::STATE_NOT_STARTED,
            ],
            'job state: compilation-running' => [
                'workflow' => new ApplicationWorkflow(
                    false,
                    new CompilationState(CompilationState::STATE_RUNNING),
                    new ExecutionState(ExecutionState::STATE_AWAITING)
                ),
                'expectedState' => WorkflowInterface::STATE_IN_PROGRESS,
            ],
            'job state: execution-running' => [
                'workflow' => new ApplicationWorkflow(
                    false,
                    new CompilationState(CompilationState::STATE_COMPLETE),
                    new ExecutionState(ExecutionState::STATE_RUNNING)
                ),
                'expectedState' => WorkflowInterface::STATE_IN_PROGRESS,
            ],
            'job state: execution-cancelled' => [
                'workflow' => new ApplicationWorkflow(
                    false,
                    new CompilationState(CompilationState::STATE_COMPLETE),
                    new ExecutionState(ExecutionState::STATE_CANCELLED)
                ),
                'expectedState' => WorkflowInterface::STATE_COMPLETE,
            ],
            'job finished, callback workflow incomplete' => [
                'workflow' => new ApplicationWorkflow(
                    false,
                    new CompilationState(CompilationState::STATE_COMPLETE),
                    new ExecutionState(ExecutionState::STATE_COMPLETE)
                ),
                'expectedState' => WorkflowInterface::STATE_IN_PROGRESS,
            ],
            'job finished, callback workflow complete' => [
                'workflow' => new ApplicationWorkflow(
                    true,
                    new CompilationState(CompilationState::STATE_COMPLETE),
                    new ExecutionState(ExecutionState::STATE_COMPLETE)
                ),
                'expectedState' => WorkflowInterface::STATE_COMPLETE,
            ],
        ];
    }
}
