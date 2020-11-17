<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model\Workflow;

use App\Entity\Job;
use App\Model\Workflow\ApplicationWorkflow;
use App\Model\Workflow\CallbackWorkflow;
use App\Tests\Mock\Entity\MockJob;
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
                'workflow' => new ApplicationWorkflow(null, CallbackWorkflow::STATE_NOT_STARTED),
                'expectedState' => ApplicationWorkflow::STATE_NOT_READY,
            ],
            'job state: compilation-awaiting' => [
                'workflow' => new ApplicationWorkflow(
                    (new MockJob())
                        ->withGetStateCall(Job::STATE_COMPILATION_AWAITING)
                        ->getMock(),
                    CallbackWorkflow::STATE_NOT_STARTED
                ),
                'expectedState' => ApplicationWorkflow::STATE_NOT_STARTED,
            ],
            'job state: compilation-running' => [
                'workflow' => new ApplicationWorkflow(
                    (new MockJob())
                        ->withGetStateCall(Job::STATE_COMPILATION_RUNNING)
                        ->withIsRunningCall(true)
                        ->getMock(),
                    CallbackWorkflow::STATE_NOT_STARTED
                ),
                'expectedState' => ApplicationWorkflow::STATE_IN_PROGRESS,
            ],
            'job state: execution-running' => [
                'workflow' => new ApplicationWorkflow(
                    (new MockJob())
                        ->withGetStateCall(Job::STATE_EXECUTION_RUNNING)
                        ->withIsRunningCall(true)
                        ->getMock(),
                    CallbackWorkflow::STATE_NOT_STARTED
                ),
                'expectedState' => ApplicationWorkflow::STATE_IN_PROGRESS,
            ],
            'job state: execution-cancelled' => [
                'workflow' => new ApplicationWorkflow(
                    (new MockJob())
                        ->withGetStateCall(Job::STATE_EXECUTION_CANCELLED)
                        ->withIsRunningCall(false)
                        ->getMock(),
                    CallbackWorkflow::STATE_NOT_STARTED
                ),
                'expectedState' => ApplicationWorkflow::STATE_COMPLETE,
            ],
            'job finished, callback workflow incomplete' => [
                'workflow' => new ApplicationWorkflow(
                    (new MockJob())
                        ->withGetStateCall(Job::STATE_EXECUTION_COMPLETE)
                        ->withIsRunningCall(false)
                        ->getMock(),
                    CallbackWorkflow::STATE_IN_PROGRESS
                ),
                'expectedState' => ApplicationWorkflow::STATE_IN_PROGRESS,
            ],
            'job finished, callback workflow complete' => [
                'workflow' => new ApplicationWorkflow(
                    (new MockJob())
                        ->withGetStateCall(Job::STATE_EXECUTION_COMPLETE)
                        ->withIsRunningCall(false)
                        ->getMock(),
                    CallbackWorkflow::STATE_COMPLETE
                ),
                'expectedState' => ApplicationWorkflow::STATE_COMPLETE,
            ],
        ];
    }
}
