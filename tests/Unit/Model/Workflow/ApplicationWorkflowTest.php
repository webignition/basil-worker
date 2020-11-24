<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model\Workflow;

use App\Model\CompilationState;
use App\Model\JobState;
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
                    new JobState(JobState::STATE_UNKNOWN),
                    false,
                    new CompilationState(CompilationState::STATE_AWAITING)
                ),
                'expectedState' => WorkflowInterface::STATE_NOT_STARTED,
            ],
            'job state: compilation-awaiting' => [
                'workflow' => new ApplicationWorkflow(
                    new JobState(JobState::STATE_COMPILATION_AWAITING),
                    false,
                    new CompilationState(CompilationState::STATE_AWAITING)
                ),
                'expectedState' => WorkflowInterface::STATE_NOT_STARTED,
            ],
            'job state: compilation-running' => [
                'workflow' => new ApplicationWorkflow(
                    new JobState(JobState::STATE_COMPILATION_RUNNING),
                    false,
                    new CompilationState(CompilationState::STATE_RUNNING)
                ),
                'expectedState' => WorkflowInterface::STATE_IN_PROGRESS,
            ],
            'job state: execution-running' => [
                'workflow' => new ApplicationWorkflow(
                    new JobState(JobState::STATE_EXECUTION_RUNNING),
                    false,
                    new CompilationState(CompilationState::STATE_COMPLETE)
                ),
                'expectedState' => WorkflowInterface::STATE_IN_PROGRESS,
            ],
            'job state: execution-cancelled' => [
                'workflow' => new ApplicationWorkflow(
                    new JobState(JobState::STATE_EXECUTION_CANCELLED),
                    false,
                    new CompilationState(CompilationState::STATE_COMPLETE)
                ),
                'expectedState' => WorkflowInterface::STATE_COMPLETE,
            ],
            'job finished, callback workflow incomplete' => [
                'workflow' => new ApplicationWorkflow(
                    new JobState(JobState::STATE_EXECUTION_COMPLETE),
                    false,
                    new CompilationState(CompilationState::STATE_COMPLETE)
                ),
                'expectedState' => WorkflowInterface::STATE_IN_PROGRESS,
            ],
            'job finished, callback workflow complete' => [
                'workflow' => new ApplicationWorkflow(
                    new JobState(JobState::STATE_EXECUTION_COMPLETE),
                    true,
                    new CompilationState(CompilationState::STATE_COMPLETE)
                ),
                'expectedState' => WorkflowInterface::STATE_COMPLETE,
            ],
        ];
    }
}
