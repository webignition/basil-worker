<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Model\CompilationState;
use App\Model\ExecutionState;
use App\Model\Workflow\ApplicationWorkflow;
use App\Model\Workflow\CallbackWorkflow;
use App\Services\ApplicationWorkflowFactory;
use App\Services\CallbackWorkflowFactory;
use App\Services\CompilationStateFactory;
use App\Services\ExecutionStateFactory;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\Services\MockCallbackWorkflowFactory;
use App\Tests\Mock\Services\MockCompilationStateFactory;
use App\Tests\Mock\Services\MockExecutionStateFactory;

class ApplicationWorkflowFactoryTest extends AbstractBaseFunctionalTest
{
    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(
        CallbackWorkflowFactory $callbackWorkflowFactory,
        CompilationStateFactory $compilationStateFactory,
        ExecutionStateFactory $executionStateFactory,
        ApplicationWorkflow $expectedApplicationWorkflow
    ) {
        $applicationWorkflowFactory = new ApplicationWorkflowFactory(
            $callbackWorkflowFactory,
            $compilationStateFactory,
            $executionStateFactory
        );

        self::assertEquals($expectedApplicationWorkflow, $applicationWorkflowFactory->create());
    }

    public function createDataProvider(): array
    {
        return [
            'job not exists' => [
                'callbackWorkflowFactory' => (new MockCallbackWorkflowFactory())
                    ->withCreateCall(new CallbackWorkflow(0, 0))
                    ->getMock(),
                'compilationStateFactory' => (new MockCompilationStateFactory())
                    ->withCreateCall(new CompilationState(CompilationState::STATE_AWAITING))
                    ->getMock(),
                'executionStateFactory' => (new MockExecutionStateFactory())
                    ->withCreateCall(new ExecutionState(ExecutionState::STATE_AWAITING))
                    ->getMock(),
                'expectedApplicationWorkflow' => new ApplicationWorkflow(
                    false,
                    new CompilationState(CompilationState::STATE_AWAITING),
                    new ExecutionState(ExecutionState::STATE_AWAITING)
                ),
            ],
            'job state: compilation-awaiting' => [
                'callbackWorkflowFactory' => (new MockCallbackWorkflowFactory())
                    ->withCreateCall(new CallbackWorkflow(0, 0))
                    ->getMock(),
                'compilationStateFactory' => (new MockCompilationStateFactory())
                    ->withCreateCall(new CompilationState(CompilationState::STATE_AWAITING))
                    ->getMock(),
                'executionStateFactory' => (new MockExecutionStateFactory())
                    ->withCreateCall(new ExecutionState(ExecutionState::STATE_AWAITING))
                    ->getMock(),
                'expectedApplicationWorkflow' => new ApplicationWorkflow(
                    false,
                    new CompilationState(CompilationState::STATE_AWAITING),
                    new ExecutionState(ExecutionState::STATE_AWAITING)
                ),
            ],
            'job state: compilation-running' => [
                'callbackWorkflowFactory' => (new MockCallbackWorkflowFactory())
                    ->withCreateCall(new CallbackWorkflow(0, 0))
                    ->getMock(),
                'compilationStateFactory' => (new MockCompilationStateFactory())
                    ->withCreateCall(new CompilationState(CompilationState::STATE_RUNNING))
                    ->getMock(),
                'executionStateFactory' => (new MockExecutionStateFactory())
                    ->withCreateCall(new ExecutionState(ExecutionState::STATE_AWAITING))
                    ->getMock(),
                'expectedApplicationWorkflow' => new ApplicationWorkflow(
                    false,
                    new CompilationState(CompilationState::STATE_RUNNING),
                    new ExecutionState(ExecutionState::STATE_AWAITING)
                ),
            ],
            'job state: execution-running' => [
                'callbackWorkflowFactory' => (new MockCallbackWorkflowFactory())
                    ->withCreateCall(new CallbackWorkflow(0, 0))
                    ->getMock(),
                'compilationStateFactory' => (new MockCompilationStateFactory())
                    ->withCreateCall(new CompilationState(CompilationState::STATE_COMPLETE))
                    ->getMock(),
                'executionStateFactory' => (new MockExecutionStateFactory())
                    ->withCreateCall(new ExecutionState(ExecutionState::STATE_RUNNING))
                    ->getMock(),
                'expectedApplicationWorkflow' => new ApplicationWorkflow(
                    false,
                    new CompilationState(CompilationState::STATE_COMPLETE),
                    new ExecutionState(ExecutionState::STATE_RUNNING)
                ),
            ],
            'job state: execution-cancelled' => [
                'callbackWorkflowFactory' => (new MockCallbackWorkflowFactory())
                    ->withCreateCall(new CallbackWorkflow(0, 0))
                    ->getMock(),
                'compilationStateFactory' => (new MockCompilationStateFactory())
                    ->withCreateCall(new CompilationState(CompilationState::STATE_COMPLETE))
                    ->getMock(),
                'executionStateFactory' => (new MockExecutionStateFactory())
                    ->withCreateCall(new ExecutionState(ExecutionState::STATE_CANCELLED))
                    ->getMock(),
                'expectedApplicationWorkflow' => new ApplicationWorkflow(
                    false,
                    new CompilationState(CompilationState::STATE_COMPLETE),
                    new ExecutionState(ExecutionState::STATE_CANCELLED)
                ),
            ],
            'job finished, callback workflow incomplete' => [
                'callbackWorkflowFactory' => (new MockCallbackWorkflowFactory())
                    ->withCreateCall(new CallbackWorkflow(0, 0))
                    ->getMock(),
                'compilationStateFactory' => (new MockCompilationStateFactory())
                    ->withCreateCall(new CompilationState(CompilationState::STATE_COMPLETE))
                    ->getMock(),
                'executionStateFactory' => (new MockExecutionStateFactory())
                    ->withCreateCall(new ExecutionState(ExecutionState::STATE_COMPLETE))
                    ->getMock(),
                'expectedApplicationWorkflow' => new ApplicationWorkflow(
                    false,
                    new CompilationState(CompilationState::STATE_COMPLETE),
                    new ExecutionState(ExecutionState::STATE_COMPLETE)
                ),
            ],
            'job finished, callback workflow complete' => [
                'callbackWorkflowFactory' => (new MockCallbackWorkflowFactory())
                    ->withCreateCall(new CallbackWorkflow(1, 1))
                    ->getMock(),
                'compilationStateFactory' => (new MockCompilationStateFactory())
                    ->withCreateCall(new CompilationState(CompilationState::STATE_COMPLETE))
                    ->getMock(),
                'executionStateFactory' => (new MockExecutionStateFactory())
                    ->withCreateCall(new ExecutionState(ExecutionState::STATE_COMPLETE))
                    ->getMock(),
                'expectedApplicationWorkflow' => new ApplicationWorkflow(
                    true,
                    new CompilationState(CompilationState::STATE_COMPLETE),
                    new ExecutionState(ExecutionState::STATE_COMPLETE)
                ),
            ],
        ];
    }
}
