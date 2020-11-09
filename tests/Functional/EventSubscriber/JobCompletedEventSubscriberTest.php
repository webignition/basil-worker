<?php

declare(strict_types=1);

namespace App\Tests\Functional\EventSubscriber;

use App\Entity\Job;
use App\Event\JobCompletedEvent;
use App\EventSubscriber\JobCompletedEventSubscriber;
use App\Services\JobStateMutator;
use App\Services\JobStore;
use App\Tests\AbstractBaseFunctionalTest;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class JobCompletedEventSubscriberTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;

    private JobCompletedEventSubscriber $eventSubscriber;
    private JobStateMutator $jobStateMutator;
    private Job $job;
    private JobStore $jobStore;

    protected function setUp(): void
    {
        parent::setUp();

        $eventSubscriber = self::$container->get(JobCompletedEventSubscriber::class);
        self::assertInstanceOf(JobCompletedEventSubscriber::class, $eventSubscriber);
        if ($eventSubscriber instanceof JobCompletedEventSubscriber) {
            $this->eventSubscriber = $eventSubscriber;
        }

        $jobStore = self::$container->get(JobStore::class);
        self::assertInstanceOf(JobStore::class, $jobStore);
        if ($jobStore instanceof JobStore) {
            $this->job = $jobStore->create('label content', 'http://example.com/callback');
            $this->jobStore = $jobStore;
        }

        $jobStateMutator = self::$container->get(JobStateMutator::class);
        self::assertInstanceOf(JobStateMutator::class, $jobStateMutator);
        if ($jobStateMutator instanceof JobStateMutator) {
            $this->jobStateMutator = $jobStateMutator;
        }
    }

    /**
     * @dataProvider setJobStateToCompletedDataProvider
     *
     * @param Job::STATE_* $startState
     * @param Job::STATE_* $expectedEndState
     */
    public function testSetJobStateToCompleted(string $startState, string $expectedEndState)
    {
        $this->job->setState($startState);
        $this->jobStore->store($this->job);
        self::assertSame($startState, $this->job->getState());

        $this->eventSubscriber->setJobStateToCompleted();
        self::assertSame($expectedEndState, $this->job->getState());
    }

    public function setJobStateToCompletedDataProvider(): array
    {
        return [
            'job state: compilation awaiting' => [
                'startState' => Job::STATE_COMPILATION_AWAITING,
                'expectedEndState' => Job::STATE_COMPILATION_AWAITING,
            ],
            'job state: compilation running' => [
                'startState' => Job::STATE_COMPILATION_RUNNING,
                'expectedEndState' => Job::STATE_COMPILATION_RUNNING,
            ],
            'job state: compilation failed' => [
                'startState' => Job::STATE_COMPILATION_FAILED,
                'expectedEndState' => Job::STATE_COMPILATION_FAILED,
            ],
            'job state: execution awaiting' => [
                'startState' => Job::STATE_EXECUTION_AWAITING,
                'expectedEndState' => Job::STATE_EXECUTION_AWAITING,
            ],
            'job state: execution running' => [
                'startState' => Job::STATE_EXECUTION_RUNNING,
                'expectedEndState' => Job::STATE_EXECUTION_COMPLETE,
            ],
            'job state: execution complete' => [
                'startState' => Job::STATE_EXECUTION_COMPLETE,
                'expectedEndState' => Job::STATE_EXECUTION_COMPLETE,
            ],
            'job state: execution cancelled' => [
                'startState' => Job::STATE_EXECUTION_CANCELLED,
                'expectedEndState' => Job::STATE_EXECUTION_CANCELLED,
            ],
        ];
    }

    public function testIntegration()
    {
        $this->jobStateMutator->setExecutionRunning();
        self::assertSame(Job::STATE_EXECUTION_RUNNING, $this->job->getState());

        $eventDispatcher = self::$container->get(EventDispatcherInterface::class);
        if ($eventDispatcher instanceof EventDispatcherInterface) {
            $eventDispatcher->dispatch(new JobCompletedEvent());
        }

        self::assertSame(Job::STATE_EXECUTION_COMPLETE, $this->job->getState());
    }
}
