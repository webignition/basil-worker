<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Callback\CallbackEntity;
use App\Entity\Callback\CallbackInterface;
use App\Entity\Job;
use App\Model\Workflow\ApplicationWorkflow;
use App\Services\ApplicationWorkflowFactory;
use App\Services\CallbackStore;
use App\Services\JobStore;
use App\Tests\AbstractBaseFunctionalTest;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class ApplicationWorkflowFactoryTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private ApplicationWorkflowFactory $applicationWorkflowFactory;
    private JobStore $jobStore;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(callable $setup, callable $expectedWorkflowCreator)
    {
        $job = $setup($this->jobStore);

        self::assertEquals(
            $expectedWorkflowCreator($job),
            $this->applicationWorkflowFactory->create()
        );
    }

    public function createDataProvider(): array
    {
        return [
            'job not exists' => [
                'setup' => function () {
                    return null;
                },
                'expectedWorkflowCreator' => function (): ApplicationWorkflow {
                    return new ApplicationWorkflow(null, false);
                },
            ],
            'job state: compilation-awaiting' => [
                'setup' => function (JobStore $jobStore): Job {
                    $job = Job::create('', '');
                    $jobStore->store($job);

                    return $job;
                },
                'expectedWorkflowCreator' => function (Job $job): ApplicationWorkflow {
                    return new ApplicationWorkflow($job, false);
                },
            ],
            'job state: compilation-running' => [
                'setup' => function (JobStore $jobStore): Job {
                    $job = Job::create('', '');
                    $job->setState(Job::STATE_COMPILATION_RUNNING);
                    $jobStore->store($job);

                    return $job;
                },
                'expectedWorkflowCreator' => function (Job $job): ApplicationWorkflow {
                    return new ApplicationWorkflow($job, false);
                },
            ],
            'job state: execution-running' => [
                'setup' => function (JobStore $jobStore): Job {
                    $job = Job::create('', '');
                    $job->setState(Job::STATE_EXECUTION_RUNNING);
                    $jobStore->store($job);

                    return $job;
                },
                'expectedWorkflowCreator' => function (Job $job): ApplicationWorkflow {
                    return new ApplicationWorkflow($job, false);
                },
            ],
            'job state: execution-cancelled' => [
                'setup' => function (JobStore $jobStore): Job {
                    $job = Job::create('', '');
                    $job->setState(Job::STATE_EXECUTION_CANCELLED);
                    $jobStore->store($job);

                    return $job;
                },
                'expectedWorkflowCreator' => function (Job $job): ApplicationWorkflow {
                    return new ApplicationWorkflow($job, false);
                },
            ],
            'job finished, callback workflow incomplete' => [
                'setup' => function (JobStore $jobStore): Job {
                    $job = Job::create('', '');
                    $job->setState(Job::STATE_EXECUTION_COMPLETE);
                    $jobStore->store($job);

                    return $job;
                },
                'expectedWorkflowCreator' => function (Job $job): ApplicationWorkflow {
                    return new ApplicationWorkflow($job, false);
                },
            ],
            'job finished, callback workflow complete' => [
                'setup' => function (JobStore $jobStore): Job {
                    $job = Job::create('', '');
                    $job->setState(Job::STATE_EXECUTION_COMPLETE);
                    $jobStore->store($job);

                    $callbackStore = self::$container->get(CallbackStore::class);
                    if ($callbackStore instanceof CallbackStore) {
                        $callback = CallbackEntity::create(CallbackInterface::TYPE_COMPILE_FAILURE, []);
                        $callback->setState(CallbackInterface::STATE_COMPLETE);

                        $callbackStore->store($callback);
                    }

                    return $job;
                },
                'expectedWorkflowCreator' => function (Job $job): ApplicationWorkflow {
                    return new ApplicationWorkflow($job, true);
                },
            ],
        ];
    }
}
