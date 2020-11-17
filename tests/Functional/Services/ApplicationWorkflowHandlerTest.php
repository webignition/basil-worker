<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Callback\CallbackEntity;
use App\Entity\Callback\CallbackInterface;
use App\Entity\Job;
use App\Services\ApplicationWorkflowHandler;
use App\Services\CallbackStore;
use App\Services\JobStore;
use App\Tests\AbstractBaseFunctionalTest;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class ApplicationWorkflowHandlerTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private ApplicationWorkflowHandler $handler;
    private JobStore $jobStore;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    /**
     * @dataProvider isCompleteDataProvider
     */
    public function testIsComplete(callable $setup, bool $expectedIsComplete)
    {
        $setup($this->jobStore);

        self::assertSame($expectedIsComplete, $this->handler->isComplete());
    }

    public function isCompleteDataProvider(): array
    {
        return [
            'job not exists' => [
                'setup' => function () {
                    return null;
                },
                'expectedIsComplete' => false,
            ],
            'job state: compilation-awaiting' => [
                'setup' => function (JobStore $jobStore): Job {
                    $job = Job::create('', '');
                    $jobStore->store($job);

                    return $job;
                },
                'expectedIsComplete' => false,
            ],
            'job state: compilation-running' => [
                'setup' => function (JobStore $jobStore): Job {
                    $job = Job::create('', '');
                    $job->setState(Job::STATE_COMPILATION_RUNNING);
                    $jobStore->store($job);

                    return $job;
                },
                'expectedIsComplete' => false,
            ],
            'job state: execution-running' => [
                'setup' => function (JobStore $jobStore): Job {
                    $job = Job::create('', '');
                    $job->setState(Job::STATE_EXECUTION_RUNNING);
                    $jobStore->store($job);

                    return $job;
                },
                'expectedIsComplete' => false,
            ],
            'job state: execution-cancelled' => [
                'setup' => function (JobStore $jobStore): Job {
                    $job = Job::create('', '');
                    $job->setState(Job::STATE_EXECUTION_CANCELLED);
                    $jobStore->store($job);

                    return $job;
                },
                'expectedIsComplete' => true,
            ],
            'job finished, callback workflow incomplete' => [
                'setup' => function (JobStore $jobStore): Job {
                    $job = Job::create('', '');
                    $job->setState(Job::STATE_EXECUTION_COMPLETE);
                    $jobStore->store($job);

                    return $job;
                },
                'expectedIsComplete' => false,
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
                'expectedIsComplete' => true,
            ],
        ];
    }
}
