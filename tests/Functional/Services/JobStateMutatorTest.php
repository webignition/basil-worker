<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Job;
use App\Entity\Test;
use App\Event\SourceCompile\SourceCompileSuccessEvent;
use App\Event\SourcesAddedEvent;
use App\Event\TestExecuteCompleteEvent;
use App\Event\TestFailedEvent;
use App\Model\Workflow\WorkflowInterface;
use App\Services\CompilationWorkflowFactory;
use App\Services\ExecutionWorkflowFactory;
use App\Services\JobStateMutator;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\MockSuiteManifest;
use App\Tests\Mock\Model\MockCompilationWorkflow;
use App\Tests\Mock\Model\MockExecutionWorkflow;
use App\Tests\Mock\Services\MockCompilationWorkflowFactory;
use App\Tests\Mock\Services\MockExecutionWorkflowFactory;
use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Services\InvokableFactory\JobGetterFactory;
use App\Tests\Services\InvokableFactory\JobMutatorFactory;
use App\Tests\Services\InvokableFactory\JobSetupInvokableFactory;
use App\Tests\Services\InvokableFactory\TestSetup;
use App\Tests\Services\InvokableFactory\TestSetupInvokableFactory;
use App\Tests\Services\InvokableHandler;
use App\Tests\Services\TestCallbackEventFactory;
use Psr\EventDispatcher\EventDispatcherInterface;
use webignition\ObjectReflector\ObjectReflector;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class JobStateMutatorTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private JobStateMutator $jobStateMutator;
    private EventDispatcherInterface $eventDispatcher;
    private TestCallbackEventFactory $testCallbackEventFactory;
    private InvokableHandler $invokableHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();

        $this->invokableHandler->invoke(JobSetupInvokableFactory::setup());
    }

    /**
     * @dataProvider setExecutionCancelledDataProvider
     *
     * @param Job::STATE_* $startState
     * @param Job::STATE_* $expectedEndState
     */
    public function testSetExecutionCancelled(string $startState, string $expectedEndState)
    {
        $job = $this->invokableHandler->invoke(JobMutatorFactory::createSetState($startState));

        self::assertSame($startState, $job->getState());

        $this->jobStateMutator->setExecutionCancelled();

        self::assertSame($expectedEndState, $job->getState());
    }

    public function setExecutionCancelledDataProvider(): array
    {
        return [
            'state: compilation-awaiting' => [
                'startState' => Job::STATE_COMPILATION_AWAITING,
                'expectedEndState' => Job::STATE_EXECUTION_CANCELLED,
            ],
            'state: compilation-running' => [
                'startState' => Job::STATE_COMPILATION_RUNNING,
                'expectedEndState' => Job::STATE_EXECUTION_CANCELLED,
            ],
            'state: compilation-failed' => [
                'startState' => Job::STATE_COMPILATION_FAILED,
                'expectedEndState' => Job::STATE_COMPILATION_FAILED,
            ],
            'state: execution-awaiting' => [
                'startState' => Job::STATE_EXECUTION_AWAITING,
                'expectedEndState' => Job::STATE_EXECUTION_CANCELLED,
            ],
            'state: execution-running' => [
                'startState' => Job::STATE_EXECUTION_RUNNING,
                'expectedEndState' => Job::STATE_EXECUTION_CANCELLED,
            ],
            'state: execution-failed' => [
                'startState' => Job::STATE_EXECUTION_FAILED,
                'expectedEndState' => Job::STATE_EXECUTION_FAILED,
            ],
            'state: execution-complete' => [
                'startState' => Job::STATE_EXECUTION_COMPLETE,
                'expectedEndState' => Job::STATE_EXECUTION_COMPLETE,
            ],
            'state: execution-cancelled' => [
                'startState' => Job::STATE_EXECUTION_CANCELLED,
                'expectedEndState' => Job::STATE_EXECUTION_CANCELLED,
            ],
        ];
    }

    /**
     * @dataProvider setExecutionCompleteDataProvider
     */
    public function testSetExecutionComplete(
        InvokableInterface $setup,
        ExecutionWorkflowFactory $executionWorkflowFactory,
        bool $expectedStateIsMutated
    ) {
        $job = $this->invokableHandler->invoke(JobGetterFactory::get());

        self::assertNotSame(Job::STATE_EXECUTION_COMPLETE, $job->getState());

        $this->invokableHandler->invoke($setup);

        ObjectReflector::setProperty(
            $this->jobStateMutator,
            JobStateMutator::class,
            'executionWorkflowFactory',
            $executionWorkflowFactory
        );

        $this->jobStateMutator->setExecutionComplete();

        self::assertSame($expectedStateIsMutated, Job::STATE_EXECUTION_COMPLETE === $job->getState());
    }

    public function setExecutionCompleteDataProvider(): array
    {
        return [
            'compilation workflow not complete' => [
                'setup' => Invokable::createEmpty(),
                'executionWorkflowFactory' => (new MockExecutionWorkflowFactory())
                    ->withCreateCall(
                        (new MockExecutionWorkflow())
                            ->withGetStateCall(WorkflowInterface::STATE_IN_PROGRESS)
                            ->getMock()
                    )
                    ->getMock(),
                'expectedStateIsMutated' => false,
            ],
            'execution workflow complete' => [
                'setup' => Invokable::createEmpty(),
                'executionWorkflowFactory' => (new MockExecutionWorkflowFactory())
                    ->withCreateCall(
                        (new MockExecutionWorkflow())
                            ->withGetStateCall(WorkflowInterface::STATE_COMPLETE)
                            ->getMock()
                    )
                    ->getMock(),
                'expectedStateIsMutated' => true,
            ],
            'execution workflow complete, job is already cancelled' => [
                'setup' => JobMutatorFactory::createSetState(Job::STATE_EXECUTION_CANCELLED),
                'executionWorkflowFactory' => (new MockExecutionWorkflowFactory())
                    ->withCreateCall(
                        (new MockExecutionWorkflow())
                            ->withGetStateCall(WorkflowInterface::STATE_COMPLETE)
                            ->getMock()
                    )
                    ->getMock(),
                'expectedStateIsMutated' => false,
            ],
        ];
    }

    /**
     * @dataProvider setCompilationFailedDataProvider
     *
     * @param Job::STATE_* $startState
     * @param Job::STATE_* $expectedEndState
     */
    public function testSetCompilationFailed(string $startState, string $expectedEndState)
    {
        $job = $this->invokableHandler->invoke(JobMutatorFactory::createSetState($startState));

        self::assertSame($startState, $job->getState());

        $this->jobStateMutator->setCompilationFailed();

        self::assertSame($expectedEndState, $job->getState());
    }

    public function setCompilationFailedDataProvider(): array
    {
        return [
            'state: compilation-awaiting' => [
                'startState' => Job::STATE_COMPILATION_AWAITING,
                'expectedEndState' => Job::STATE_COMPILATION_AWAITING,
            ],
            'state: compilation-running' => [
                'startState' => Job::STATE_COMPILATION_RUNNING,
                'expectedEndState' => Job::STATE_COMPILATION_FAILED,
            ],
            'state: compilation-failed' => [
                'startState' => Job::STATE_COMPILATION_FAILED,
                'expectedEndState' => Job::STATE_COMPILATION_FAILED,
            ],
            'state: execution-awaiting' => [
                'startState' => Job::STATE_EXECUTION_AWAITING,
                'expectedEndState' => Job::STATE_EXECUTION_AWAITING,
            ],
            'state: execution-running' => [
                'startState' => Job::STATE_EXECUTION_RUNNING,
                'expectedEndState' => Job::STATE_EXECUTION_RUNNING,
            ],
            'state: execution-failed' => [
                'startState' => Job::STATE_EXECUTION_FAILED,
                'expectedEndState' => Job::STATE_EXECUTION_FAILED,
            ],
            'state: execution-complete' => [
                'startState' => Job::STATE_EXECUTION_COMPLETE,
                'expectedEndState' => Job::STATE_EXECUTION_COMPLETE,
            ],
            'state: execution-cancelled' => [
                'startState' => Job::STATE_EXECUTION_CANCELLED,
                'expectedEndState' => Job::STATE_EXECUTION_CANCELLED,
            ],
        ];
    }

    /**
     * @dataProvider setCompilationRunningDataProvider
     *
     * @param Job::STATE_* $startState
     * @param Job::STATE_* $expectedEndState
     */
    public function testSetCompilationRunning(string $startState, string $expectedEndState)
    {
        $job = $this->invokableHandler->invoke(JobMutatorFactory::createSetState($startState));

        self::assertSame($startState, $job->getState());

        $this->jobStateMutator->setCompilationRunning();

        self::assertSame($expectedEndState, $job->getState());
    }

    public function setCompilationRunningDataProvider(): array
    {
        return [
            'state: compilation-awaiting' => [
                'startState' => Job::STATE_COMPILATION_AWAITING,
                'expectedEndState' => Job::STATE_COMPILATION_RUNNING,
            ],
            'state: compilation-running' => [
                'startState' => Job::STATE_COMPILATION_RUNNING,
                'expectedEndState' => Job::STATE_COMPILATION_RUNNING,
            ],
            'state: compilation-failed' => [
                'startState' => Job::STATE_COMPILATION_FAILED,
                'expectedEndState' => Job::STATE_COMPILATION_FAILED,
            ],
            'state: execution-awaiting' => [
                'startState' => Job::STATE_EXECUTION_AWAITING,
                'expectedEndState' => Job::STATE_EXECUTION_AWAITING,
            ],
            'state: execution-running' => [
                'startState' => Job::STATE_EXECUTION_RUNNING,
                'expectedEndState' => Job::STATE_EXECUTION_RUNNING,
            ],
            'state: execution-failed' => [
                'startState' => Job::STATE_EXECUTION_FAILED,
                'expectedEndState' => Job::STATE_EXECUTION_FAILED,
            ],
            'state: execution-complete' => [
                'startState' => Job::STATE_EXECUTION_COMPLETE,
                'expectedEndState' => Job::STATE_EXECUTION_COMPLETE,
            ],
            'state: execution-cancelled' => [
                'startState' => Job::STATE_EXECUTION_CANCELLED,
                'expectedEndState' => Job::STATE_EXECUTION_CANCELLED,
            ],
        ];
    }

    /**
     * @dataProvider setExecutionAwaitingDataProvider
     */
    public function testSetExecutionAwaiting(
        CompilationWorkflowFactory $compilationWorkflowFactory,
        ExecutionWorkflowFactory $executionWorkflowFactory,
        bool $expectedStateIsMutated
    ) {
        $job = $this->invokableHandler->invoke(JobGetterFactory::get());

        self::assertNotSame(Job::STATE_EXECUTION_AWAITING, $job->getState());

        ObjectReflector::setProperty(
            $this->jobStateMutator,
            JobStateMutator::class,
            'compilationWorkflowFactory',
            $compilationWorkflowFactory
        );

        ObjectReflector::setProperty(
            $this->jobStateMutator,
            JobStateMutator::class,
            'executionWorkflowFactory',
            $executionWorkflowFactory
        );

        $this->jobStateMutator->setExecutionAwaiting();

        self::assertSame($expectedStateIsMutated, Job::STATE_EXECUTION_AWAITING === $job->getState());
    }

    public function setExecutionAwaitingDataProvider(): array
    {
        return [
            'compilation workflow not complete' => [
                'compilationWorkflowFactory' => (new MockCompilationWorkflowFactory())
                    ->withCreateCall(
                        (new MockCompilationWorkflow())
                            ->withGetStateCall(WorkflowInterface::STATE_NOT_READY)
                            ->getMock()
                    )
                    ->getMock(),
                'executionWorkflowFactory' => (new MockExecutionWorkflowFactory())
                    ->getMock(),
                'expectedStateIsMutated' => false,
            ],
            'execution workflow not ready to execute' => [
                'compilationWorkflowFactory' => (new MockCompilationWorkflowFactory())
                    ->withCreateCall(
                        (new MockCompilationWorkflow())
                            ->withGetStateCall(WorkflowInterface::STATE_COMPLETE)
                            ->getMock()
                    )
                    ->getMock(),
                'executionWorkflowFactory' => (new MockExecutionWorkflowFactory())
                    ->withCreateCall(
                        (new MockExecutionWorkflow())
                            ->withGetStateCall(WorkflowInterface::STATE_NOT_READY)
                            ->getMock()
                    )
                    ->getMock(),
                'expectedStateIsMutated' => false,
            ],
            'compilation workflow complete, execution workflow ready to execute' => [
                'compilationWorkflowFactory' => (new MockCompilationWorkflowFactory())
                    ->withCreateCall(
                        (new MockCompilationWorkflow())
                            ->withGetStateCall(WorkflowInterface::STATE_COMPLETE)
                            ->getMock()
                    )
                    ->getMock(),
                'executionWorkflowFactory' => (new MockExecutionWorkflowFactory())
                    ->withCreateCall(
                        (new MockExecutionWorkflow())
                            ->withGetStateCall(WorkflowInterface::STATE_NOT_STARTED)
                            ->getMock()
                    )
                    ->getMock(),
                'expectedStateIsMutated' => true,
            ],
        ];
    }

    public function testSubscribesToSourceCompileFailureEvent()
    {
        $job = $this->invokableHandler->invoke(JobMutatorFactory::createSetState(Job::STATE_COMPILATION_RUNNING));

        $event = $this->testCallbackEventFactory->createEmptyPayloadSourceCompileFailureEvent();

        $this->eventDispatcher->dispatch($event);

        self::assertSame(Job::STATE_COMPILATION_FAILED, $job->getState());
    }

    public function testSubscribesToSourcesAddedEvent()
    {
        $job = $this->invokableHandler->invoke(JobMutatorFactory::createSetState(Job::STATE_COMPILATION_AWAITING));
        $this->invokableHandler->invoke(JobMutatorFactory::createSetSources([
            'Test/test1.yml',
        ]));

        $this->eventDispatcher->dispatch(new SourcesAddedEvent());

        self::assertSame(Job::STATE_COMPILATION_RUNNING, $job->getState());
    }

    public function testSubscribesToTestFailedEvent()
    {
        $job = $this->invokableHandler->invoke(JobGetterFactory::get());
        self::assertSame(Job::STATE_COMPILATION_AWAITING, $job->getState());

        $test = $this->invokableHandler->invoke(TestSetupInvokableFactory::setup(
            (new TestSetup())
                ->withState(Test::STATE_FAILED)
        ));

        $this->eventDispatcher->dispatch(new TestFailedEvent($test));

        self::assertSame(Job::STATE_EXECUTION_CANCELLED, $job->getState());
    }

    public function testSubscribesToSourceCompileSuccessEvent()
    {
        $job = $this->invokableHandler->invoke(JobGetterFactory::get());
        self::assertNotSame(Job::STATE_EXECUTION_AWAITING, $job->getState());

        ObjectReflector::setProperty(
            $this->jobStateMutator,
            JobStateMutator::class,
            'compilationWorkflowFactory',
            (new MockCompilationWorkflowFactory())
                ->withCreateCall(
                    (new MockCompilationWorkflow())
                        ->withGetStateCall(WorkflowInterface::STATE_COMPLETE)
                        ->getMock()
                )
                ->getMock()
        );

        ObjectReflector::setProperty(
            $this->jobStateMutator,
            JobStateMutator::class,
            'executionWorkflowFactory',
            (new MockExecutionWorkflowFactory())
                ->withCreateCall(
                    (new MockExecutionWorkflow())
                        ->withGetStateCall(WorkflowInterface::STATE_NOT_STARTED)
                        ->getMock()
                )
                ->getMock()
        );

        $event = new SourceCompileSuccessEvent(
            '/app/source/Test/test.yml',
            (new MockSuiteManifest())
                ->withGetTestManifestsCall([])
                ->getMock()
        );

        $this->eventDispatcher->dispatch($event);

        self::assertSame(Job::STATE_EXECUTION_AWAITING, $job->getState());
    }

    public function testSubscribesToTestExecuteCompleteEvent()
    {
        $job = $this->invokableHandler->invoke(JobGetterFactory::get());
        self::assertNotSame(Job::STATE_EXECUTION_COMPLETE, $job->getState());

        ObjectReflector::setProperty(
            $this->jobStateMutator,
            JobStateMutator::class,
            'executionWorkflowFactory',
            (new MockExecutionWorkflowFactory())
                ->withCreateCall(
                    (new MockExecutionWorkflow())
                        ->withGetStateCall(WorkflowInterface::STATE_COMPLETE)
                        ->getMock()
                )
                ->getMock()
        );

        $test = $this->invokableHandler->invoke(TestSetupInvokableFactory::setup());

        $this->eventDispatcher->dispatch(new TestExecuteCompleteEvent($test));

        self::assertSame(Job::STATE_EXECUTION_COMPLETE, $job->getState());
    }
}
