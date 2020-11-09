<?php

declare(strict_types=1);

namespace App\Tests\Functional\EventSubscriber;

use App\Entity\Job;
use App\Event\JobCancelledEvent;
use App\EventSubscriber\JobCancelledEventSubscriber;
use App\Services\JobStateMutator;
use App\Services\JobStore;
use App\Tests\AbstractBaseFunctionalTest;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class JobCancelledEventSubscriberTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;

    private JobCancelledEventSubscriber $eventSubscriber;
    private JobStateMutator $jobStateMutator;
    private Job $job;
    private JobStore $jobStore;

    protected function setUp(): void
    {
        parent::setUp();

        $eventSubscriber = self::$container->get(JobCancelledEventSubscriber::class);
        self::assertInstanceOf(JobCancelledEventSubscriber::class, $eventSubscriber);
        if ($eventSubscriber instanceof JobCancelledEventSubscriber) {
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
     * @dataProvider setJobStateToCancelledDataProvider
     *
     * @param Job::STATE_* $startState
     * @param Job::STATE_* $expectedEndState
     */
    public function testSetJobStateToCancelled(string $startState, string $expectedEndState)
    {
        $this->job->setState($startState);
        $this->jobStore->store($this->job);
        self::assertSame($startState, $this->job->getState());

        $this->eventSubscriber->setJobStateToCancelled();
        self::assertSame($expectedEndState, $this->job->getState());
    }

    public function setJobStateToCancelledDataProvider(): array
    {
        return [
            'job state: compilation awaiting' => [
                'startState' => Job::STATE_COMPILATION_AWAITING,
                'expectedEndState' => Job::STATE_EXECUTION_CANCELLED,
            ],
            'job state: compilation running' => [
                'startState' => Job::STATE_COMPILATION_RUNNING,
                'expectedEndState' => Job::STATE_EXECUTION_CANCELLED,
            ],
            'job state: compilation failed' => [
                'startState' => Job::STATE_COMPILATION_FAILED,
                'expectedEndState' => Job::STATE_COMPILATION_FAILED,
            ],
            'job state: execution awaiting' => [
                'startState' => Job::STATE_EXECUTION_AWAITING,
                'expectedEndState' => Job::STATE_EXECUTION_CANCELLED,
            ],
            'job state: execution running' => [
                'startState' => Job::STATE_EXECUTION_RUNNING,
                'expectedEndState' => Job::STATE_EXECUTION_CANCELLED,
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
            $eventDispatcher->dispatch(new JobCancelledEvent());
        }

        self::assertSame(Job::STATE_EXECUTION_CANCELLED, $this->job->getState());
    }
}
