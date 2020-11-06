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
        }

        $jobStateMutator = self::$container->get(JobStateMutator::class);
        self::assertInstanceOf(JobStateMutator::class, $jobStateMutator);
        if ($jobStateMutator instanceof JobStateMutator) {
            $this->jobStateMutator = $jobStateMutator;
        }
    }

    /**
     * @dataProvider setJobStateToCancelledDataProvider
     */
    public function testSetJobStateToCancelled(callable $jobMutator, string $expectedJobState)
    {
        $jobMutator($this->jobStateMutator, $this->job);

        $this->eventSubscriber->setJobStateToCancelled();

        self::assertSame($expectedJobState, $this->job->getState());
    }

    public function setJobStateToCancelledDataProvider(): array
    {
        return [
            'job state: compilation awaiting' => [
                'jobMutator' => function () {
                },
                'expectedJobState' => Job::STATE_EXECUTION_CANCELLED,
            ],
            'job state: compilation running' => [
                'jobMutator' => function (JobStateMutator $jobStateMutator, Job $job) {
                    $jobStateMutator->setCompilationRunning();
                    self::assertSame(Job::STATE_COMPILATION_RUNNING, $job->getState());
                },
                'expectedJobState' => Job::STATE_EXECUTION_CANCELLED,
            ],
            'job state: compilation failed' => [
                'jobMutator' => function (JobStateMutator $jobStateMutator, Job $job) {
                    $jobStateMutator->setCompilationFailed();
                    self::assertSame(Job::STATE_COMPILATION_FAILED, $job->getState());
                },
                'expectedJobState' => Job::STATE_COMPILATION_FAILED,
            ],
            'job state: execution awaiting' => [
                'jobMutator' => function (JobStateMutator $jobStateMutator, Job $job) {
                    $jobStateMutator->setExecutionAwaiting();
                    self::assertSame(Job::STATE_EXECUTION_AWAITING, $job->getState());
                },
                'expectedJobState' => Job::STATE_EXECUTION_CANCELLED,
            ],
            'job state: execution running' => [
                'jobMutator' => function (JobStateMutator $jobStateMutator, Job $job) {
                    $jobStateMutator->setExecutionRunning();
                    self::assertSame(Job::STATE_EXECUTION_RUNNING, $job->getState());
                },
                'expectedJobState' => Job::STATE_EXECUTION_CANCELLED,
            ],
            'job state: execution complete' => [
                'jobMutator' => function (JobStateMutator $jobStateMutator, Job $job) {
                    $jobStateMutator->setExecutionComplete();
                    self::assertSame(Job::STATE_EXECUTION_COMPLETE, $job->getState());
                },
                'expectedJobState' => Job::STATE_EXECUTION_COMPLETE,
            ],
            'job state: execution cancelled' => [
                'jobMutator' => function (JobStateMutator $jobStateMutator, Job $job) {
                    $jobStateMutator->setExecutionCancelled();
                    self::assertSame(Job::STATE_EXECUTION_CANCELLED, $job->getState());
                },
                'expectedJobState' => Job::STATE_EXECUTION_CANCELLED,
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
