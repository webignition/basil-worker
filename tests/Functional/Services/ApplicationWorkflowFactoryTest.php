<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Callback\CallbackInterface;
use App\Entity\Job;
use App\Model\Workflow\ApplicationWorkflow;
use App\Services\ApplicationWorkflowFactory;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableCollection;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Services\InvokableFactory\CallbackSetup;
use App\Tests\Services\InvokableFactory\CallbackSetupInvokableFactory;
use App\Tests\Services\InvokableFactory\JobGetterFactory;
use App\Tests\Services\InvokableFactory\JobSetup;
use App\Tests\Services\InvokableFactory\JobSetupInvokableFactory;
use App\Tests\Services\InvokableHandler;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class ApplicationWorkflowFactoryTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private ApplicationWorkflowFactory $applicationWorkflowFactory;
    private InvokableHandler $invokableHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(InvokableInterface $setup, callable $expectedWorkflowCreator)
    {
        $job = $this->invokableHandler->invoke($setup);

        self::assertEquals(
            $expectedWorkflowCreator($job),
            $this->applicationWorkflowFactory->create()
        );
    }

    public function createDataProvider(): array
    {
        return [
            'job not exists' => [
                'setup' => Invokable::createEmpty(),
                'expectedWorkflowCreator' => function (): ApplicationWorkflow {
                    return new ApplicationWorkflow(null, false);
                },
            ],
            'job state: compilation-awaiting' => [
                'setup' => JobSetupInvokableFactory::setup(
                    (new JobSetup())
                        ->withState(Job::STATE_COMPILATION_AWAITING),
                ),
                'expectedWorkflowCreator' => function (Job $job): ApplicationWorkflow {
                    return new ApplicationWorkflow($job, false);
                },
            ],
            'job state: compilation-running' => [
                'setup' => JobSetupInvokableFactory::setup(
                    (new JobSetup())
                        ->withState(Job::STATE_COMPILATION_RUNNING),
                ),
                'expectedWorkflowCreator' => function (Job $job): ApplicationWorkflow {
                    return new ApplicationWorkflow($job, false);
                },
            ],
            'job state: execution-running' => [
                'setup' => JobSetupInvokableFactory::setup(
                    (new JobSetup())
                        ->withState(Job::STATE_EXECUTION_RUNNING),
                ),
                'expectedWorkflowCreator' => function (Job $job): ApplicationWorkflow {
                    return new ApplicationWorkflow($job, false);
                },
            ],
            'job state: execution-cancelled' => [
                'setup' => JobSetupInvokableFactory::setup(
                    (new JobSetup())
                        ->withState(Job::STATE_EXECUTION_CANCELLED),
                ),
                'expectedWorkflowCreator' => function (Job $job): ApplicationWorkflow {
                    return new ApplicationWorkflow($job, false);
                },
            ],
            'job finished, callback workflow incomplete' => [
                'setup' => JobSetupInvokableFactory::setup(
                    (new JobSetup())
                        ->withState(Job::STATE_EXECUTION_COMPLETE),
                ),
                'expectedWorkflowCreator' => function (Job $job): ApplicationWorkflow {
                    return new ApplicationWorkflow($job, false);
                },
            ],
            'job finished, callback workflow complete' => [
                'setup' => new InvokableCollection([
                    JobSetupInvokableFactory::setup(
                        (new JobSetup())
                            ->withState(Job::STATE_EXECUTION_COMPLETE),
                    ),
                    CallbackSetupInvokableFactory::setup(
                        (new CallbackSetup())
                            ->withState(CallbackInterface::STATE_COMPLETE),
                    ),
                    JobGetterFactory::get(),
                ]),
                'expectedWorkflowCreator' => function (Job $job): ApplicationWorkflow {
                    return new ApplicationWorkflow($job, true);
                },
            ],
        ];
    }
}
