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
        }

        $jobStateMutator = self::$container->get(JobStateMutator::class);
        self::assertInstanceOf(JobStateMutator::class, $jobStateMutator);
        if ($jobStateMutator instanceof JobStateMutator) {
            $this->jobStateMutator = $jobStateMutator;
        }
    }

    /**
     * @dataProvider setJobStateToCompletedDataProvider
     */
    public function testSetJobStateToCompleted(callable $jobMutator, string $expectedJobState)
    {
        $jobMutator($this->jobStateMutator, $this->job);

        $this->eventSubscriber->setJobStateToCompleted();

        self::assertSame($expectedJobState, $this->job->getState());
    }

    public function setJobStateToCompletedDataProvider(): array
    {
        return [
            'job state: compilation awaiting' => [
                'jobMutator' => function () {
                },
                'expectedJobState' => Job::STATE_COMPILATION_AWAITING,
            ],
            'job state: compilation running' => [
                'jobMutator' => function (JobStateMutator $jobStateMutator, Job $job) {
                    $jobStateMutator->setCompilationRunning();
                    self::assertSame(Job::STATE_COMPILATION_RUNNING, $job->getState());
                },
                'expectedJobState' => Job::STATE_COMPILATION_RUNNING,
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
                'expectedJobState' => Job::STATE_EXECUTION_AWAITING,
            ],
            'job state: execution running' => [
                'jobMutator' => function (JobStateMutator $jobStateMutator, Job $job) {
                    $jobStateMutator->setExecutionRunning();
                    self::assertSame(Job::STATE_EXECUTION_RUNNING, $job->getState());
                },
                'expectedJobState' => Job::STATE_EXECUTION_COMPLETE,
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
            $eventDispatcher->dispatch(new JobCompletedEvent());
        }

        self::assertSame(Job::STATE_EXECUTION_COMPLETE, $this->job->getState());
    }
}
