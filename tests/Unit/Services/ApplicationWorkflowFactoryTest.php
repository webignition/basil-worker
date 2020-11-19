<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Model\JobState;
use App\Model\Workflow\ApplicationWorkflow;
use App\Model\Workflow\CallbackWorkflow;
use App\Services\ApplicationWorkflowFactory;
use App\Services\CallbackWorkflowFactory;
use App\Services\JobStateFactory;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\Services\MockCallbackWorkflowFactory;
use App\Tests\Mock\Services\MockJobStateFactory;

class ApplicationWorkflowFactoryTest extends AbstractBaseFunctionalTest
{
    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(
        CallbackWorkflowFactory $callbackWorkflowFactory,
        JobStateFactory $jobStateFactory,
        ApplicationWorkflow $expectedApplicationWorkflow
    ) {
        $applicationWorkflowFactory = new ApplicationWorkflowFactory($callbackWorkflowFactory, $jobStateFactory);

        self::assertEquals($expectedApplicationWorkflow, $applicationWorkflowFactory->create());
    }

    public function createDataProvider(): array
    {
        return [
            'job not exists' => [
                'callbackWorkflowFactory' => (new MockCallbackWorkflowFactory())
                    ->withCreateCall(new CallbackWorkflow(0, 0))
                    ->getMock(),
                'jobStateFactory' => (new MockJobStateFactory())
                    ->withCreateCall(new JobState(JobState::STATE_COMPILATION_AWAITING))
                    ->getMock(),
                'expectedApplicationWorkflow' => new ApplicationWorkflow(
                    new JobState(JobState::STATE_COMPILATION_AWAITING),
                    false
                ),
            ],
            'job state: compilation-awaiting' => [
                'callbackWorkflowFactory' => (new MockCallbackWorkflowFactory())
                    ->withCreateCall(new CallbackWorkflow(0, 0))
                    ->getMock(),
                'jobStateFactory' => (new MockJobStateFactory())
                    ->withCreateCall(new JobState(JobState::STATE_COMPILATION_AWAITING))
                    ->getMock(),
                'expectedApplicationWorkflow' => new ApplicationWorkflow(
                    new JobState(JobState::STATE_COMPILATION_AWAITING),
                    false
                ),
            ],
            'job state: compilation-running' => [
                'callbackWorkflowFactory' => (new MockCallbackWorkflowFactory())
                    ->withCreateCall(new CallbackWorkflow(0, 0))
                    ->getMock(),
                'jobStateFactory' => (new MockJobStateFactory())
                    ->withCreateCall(new JobState(JobState::STATE_COMPILATION_RUNNING))
                    ->getMock(),
                'expectedApplicationWorkflow' => new ApplicationWorkflow(
                    new JobState(JobState::STATE_COMPILATION_RUNNING),
                    false
                ),
            ],
            'job state: execution-running' => [
                'callbackWorkflowFactory' => (new MockCallbackWorkflowFactory())
                    ->withCreateCall(new CallbackWorkflow(0, 0))
                    ->getMock(),
                'jobStateFactory' => (new MockJobStateFactory())
                    ->withCreateCall(new JobState(JobState::STATE_EXECUTION_RUNNING))
                    ->getMock(),
                'expectedApplicationWorkflow' => new ApplicationWorkflow(
                    new JobState(JobState::STATE_EXECUTION_RUNNING),
                    false
                ),
            ],
            'job state: execution-cancelled' => [
                'callbackWorkflowFactory' => (new MockCallbackWorkflowFactory())
                    ->withCreateCall(new CallbackWorkflow(0, 0))
                    ->getMock(),
                'jobStateFactory' => (new MockJobStateFactory())
                    ->withCreateCall(new JobState(JobState::STATE_EXECUTION_CANCELLED))
                    ->getMock(),
                'expectedApplicationWorkflow' => new ApplicationWorkflow(
                    new JobState(JobState::STATE_EXECUTION_CANCELLED),
                    false
                ),
            ],
            'job finished, callback workflow incomplete' => [
                'callbackWorkflowFactory' => (new MockCallbackWorkflowFactory())
                    ->withCreateCall(new CallbackWorkflow(0, 0))
                    ->getMock(),
                'jobStateFactory' => (new MockJobStateFactory())
                    ->withCreateCall(new JobState(JobState::STATE_EXECUTION_COMPLETE))
                    ->getMock(),
                'expectedApplicationWorkflow' => new ApplicationWorkflow(
                    new JobState(JobState::STATE_EXECUTION_COMPLETE),
                    false
                ),
            ],
            'job finished, callback workflow complete' => [
                'callbackWorkflowFactory' => (new MockCallbackWorkflowFactory())
                    ->withCreateCall(new CallbackWorkflow(1, 1))
                    ->getMock(),
                'jobStateFactory' => (new MockJobStateFactory())
                    ->withCreateCall(new JobState(JobState::STATE_EXECUTION_COMPLETE))
                    ->getMock(),
                'expectedApplicationWorkflow' => new ApplicationWorkflow(
                    new JobState(JobState::STATE_EXECUTION_COMPLETE),
                    true
                ),
            ],
        ];
    }
}
