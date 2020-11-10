<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Job;
use App\Entity\Test;
use App\Entity\TestConfiguration;
use App\Event\SourceCompile\SourceCompileFailureEvent;
use App\Event\SourceCompile\SourceCompileSuccessEvent;
use App\Event\SourcesAddedEvent;
use App\Event\TestExecuteCompleteEvent;
use App\Event\TestFailedEvent;
use App\Services\CompilationWorkflowHandler;
use App\Services\ExecutionWorkflowHandler;
use App\Services\JobStateMutator;
use App\Services\JobStore;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\MockSuiteManifest;
use App\Tests\Mock\Services\MockCompilationWorkflowHandler;
use App\Tests\Mock\Services\MockExecutionWorkflowHandler;
use App\Tests\Services\TestTestFactory;
use Psr\EventDispatcher\EventDispatcherInterface;
use webignition\BasilCompilerModels\ErrorOutputInterface;
use webignition\ObjectReflector\ObjectReflector;

class JobStateMutatorTest extends AbstractBaseFunctionalTest
{
    private JobStateMutator $jobStateMutator;
    private JobStore $jobStore;
    private Job $job;
    private EventDispatcherInterface $eventDispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $jobStateMutator = self::$container->get(JobStateMutator::class);
        self::assertInstanceOf(JobStateMutator::class, $jobStateMutator);
        if ($jobStateMutator instanceof JobStateMutator) {
            $this->jobStateMutator = $jobStateMutator;
        }

        $jobStore = self::$container->get(JobStore::class);
        self::assertInstanceOf(JobStore::class, $jobStore);
        if ($jobStore instanceof JobStore) {
            $this->job = $jobStore->create(md5('label content'), 'http://example.com/callback');
            $this->jobStore = $jobStore;
        }

        $eventDispatcher = self::$container->get(EventDispatcherInterface::class);
        self::assertInstanceOf(EventDispatcherInterface::class, $eventDispatcher);
        if ($eventDispatcher instanceof EventDispatcherInterface) {
            $this->eventDispatcher = $eventDispatcher;
        }
    }

    /**
     * @dataProvider setExecutionCancelledDataProvider
     *
     * @param Job::STATE_* $startState
     * @param Job::STATE_* $expectedEndState
     */
    public function testSetExecutionCancelled(string $startState, string $expectedEndState)
    {
        $this->job->setState($startState);
        $this->jobStore->store($this->job);
        self::assertSame($startState, $this->job->getState());

        $this->jobStateMutator->setExecutionCancelled();

        self::assertSame($expectedEndState, $this->job->getState());
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
        ExecutionWorkflowHandler $executionWorkflowHandler,
        bool $expectedStateIsMutated
    ) {
        self::assertNotSame(Job::STATE_EXECUTION_COMPLETE, $this->job->getState());

        ObjectReflector::setProperty(
            $this->jobStateMutator,
            JobStateMutator::class,
            'executionWorkflowHandler',
            $executionWorkflowHandler
        );

        $this->jobStateMutator->setExecutionComplete();

        self::assertSame($expectedStateIsMutated, Job::STATE_EXECUTION_COMPLETE === $this->job->getState());
    }

    public function setExecutionCompleteDataProvider(): array
    {
        return [
            'compilation workflow not complete' => [
                'executionWorkflowHandler' => (new MockExecutionWorkflowHandler())
                    ->withIsCompleteCall(false)
                    ->getMock(),
                'expectedStateIsMutated' => false,
            ],
            'compilation workflow complete' => [
                'executionWorkflowHandler' => (new MockExecutionWorkflowHandler())
                    ->withIsCompleteCall(true)
                    ->getMock(),
                'expectedStateIsMutated' => true,
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
        $this->job->setState($startState);
        $this->jobStore->store($this->job);
        self::assertSame($startState, $this->job->getState());

        $this->jobStateMutator->setCompilationFailed();

        self::assertSame($expectedEndState, $this->job->getState());
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
        $this->job->setState($startState);
        $this->jobStore->store($this->job);
        self::assertSame($startState, $this->job->getState());

        $this->jobStateMutator->setCompilationRunning();

        self::assertSame($expectedEndState, $this->job->getState());
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
        CompilationWorkflowHandler $compilationWorkflowHandler,
        ExecutionWorkflowHandler $executionWorkflowHandler,
        bool $expectedStateIsMutated
    ) {
        self::assertNotSame(Job::STATE_EXECUTION_AWAITING, $this->job->getState());

        ObjectReflector::setProperty(
            $this->jobStateMutator,
            JobStateMutator::class,
            'compilationWorkflowHandler',
            $compilationWorkflowHandler
        );

        ObjectReflector::setProperty(
            $this->jobStateMutator,
            JobStateMutator::class,
            'executionWorkflowHandler',
            $executionWorkflowHandler
        );

        $this->jobStateMutator->setExecutionAwaiting();

        self::assertSame($expectedStateIsMutated, Job::STATE_EXECUTION_AWAITING === $this->job->getState());
    }

    public function setExecutionAwaitingDataProvider(): array
    {
        return [
            'compilation workflow not complete' => [
                'compilationWorkflowHandler' => (new MockCompilationWorkflowHandler())
                    ->withIsCompleteCall(false)
                    ->getMock(),
                'executionWorkflowHandler' => (new MockExecutionWorkflowHandler())
                    ->getMock(),
                'expectedStateIsMutated' => false,
            ],
            'execution workflow not ready to execute' => [
                'compilationWorkflowHandler' => (new MockCompilationWorkflowHandler())
                    ->withIsCompleteCall(true)
                    ->getMock(),
                'executionWorkflowHandler' => (new MockExecutionWorkflowHandler())
                    ->withIsReadyToExecuteCall(false)
                    ->getMock(),
                'expectedStateIsMutated' => false,
            ],
            'compilation workflow complete, execution workflow ready to execute' => [
                'compilationWorkflowHandler' => (new MockCompilationWorkflowHandler())
                    ->withIsCompleteCall(true)
                    ->getMock(),
                'executionWorkflowHandler' => (new MockExecutionWorkflowHandler())
                    ->withIsReadyToExecuteCall(true)
                    ->getMock(),
                'expectedStateIsMutated' => true,
            ],
        ];
    }

    public function testSubscribesToSourceCompileFailureEvent()
    {
        $this->job->setState(Job::STATE_COMPILATION_RUNNING);
        $this->jobStore->store($this->job);

        $this->eventDispatcher->dispatch(
            new SourceCompileFailureEvent('source', \Mockery::mock(ErrorOutputInterface::class))
        );

        self::assertSame(Job::STATE_COMPILATION_FAILED, $this->job->getState());
    }

    public function testSubscribesToSourcesAddedEvent()
    {
        $this->job->setState(Job::STATE_COMPILATION_AWAITING);
        $this->job->setSources([
            'Test/test1.yml',
        ]);
        $this->jobStore->store($this->job);

        $this->eventDispatcher->dispatch(new SourcesAddedEvent());

        self::assertSame(Job::STATE_COMPILATION_RUNNING, $this->job->getState());
    }

    public function testSubscribesToTestFailedEvent()
    {
        self::assertSame(Job::STATE_COMPILATION_AWAITING, $this->job->getState());

        $testFactory = self::$container->get(TestTestFactory::class);
        self::assertInstanceOf(TestTestFactory::class, $testFactory);
        if ($testFactory instanceof TestTestFactory) {
            $test = $testFactory->create(
                TestConfiguration::create('chrome', 'http://example.com'),
                '/app/source/Test/test.yml',
                '/app/tests/GeneratedTest.php',
                1,
                Test::STATE_FAILED
            );

            $event = new TestFailedEvent($test);

            $this->eventDispatcher->dispatch($event);
        }

        self::assertSame(Job::STATE_EXECUTION_CANCELLED, $this->job->getState());
    }

    public function testSubscribesToSourceCompileSuccessEvent()
    {
        self::assertNotSame(Job::STATE_EXECUTION_AWAITING, $this->job->getState());

        ObjectReflector::setProperty(
            $this->jobStateMutator,
            JobStateMutator::class,
            'compilationWorkflowHandler',
            (new MockCompilationWorkflowHandler())
                ->withIsCompleteCall(true)
                ->getMock()
        );

        ObjectReflector::setProperty(
            $this->jobStateMutator,
            JobStateMutator::class,
            'executionWorkflowHandler',
            (new MockExecutionWorkflowHandler())
                ->withIsReadyToExecuteCall(true)
                ->getMock()
        );

        $event = new SourceCompileSuccessEvent(
            '/app/source/Test/test.yml',
            (new MockSuiteManifest())
                ->withGetTestManifestsCall([])
                ->getMock()
        );

        $this->eventDispatcher->dispatch($event);

        self::assertSame(Job::STATE_EXECUTION_AWAITING, $this->job->getState());
    }

    public function testSubscribesToTestExecuteCompleteEvent()
    {
        self::assertNotSame(Job::STATE_EXECUTION_COMPLETE, $this->job->getState());

        ObjectReflector::setProperty(
            $this->jobStateMutator,
            JobStateMutator::class,
            'executionWorkflowHandler',
            (new MockExecutionWorkflowHandler())
                ->withIsCompleteCall(true)
                ->getMock()
        );

        $testFactory = self::$container->get(TestTestFactory::class);
        self::assertInstanceOf(TestTestFactory::class, $testFactory);
        if ($testFactory instanceof TestTestFactory) {
            $test = $testFactory->create(
                TestConfiguration::create('chrome', 'http://example.com'),
                '/tests/test1.yml',
                '/generated/GeneratedTest.php',
                1
            );

            $event = new TestExecuteCompleteEvent($test);
            $this->eventDispatcher->dispatch($event);
        }

        self::assertSame(Job::STATE_EXECUTION_COMPLETE, $this->job->getState());
    }
}
