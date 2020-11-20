<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Callback\CallbackInterface;
use App\Entity\Test;
use App\Model\JobState;
use App\Services\JobStateFactory;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableCollection;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Services\InvokableFactory\CallbackSetup;
use App\Tests\Services\InvokableFactory\CallbackSetupInvokableFactory;
use App\Tests\Services\InvokableFactory\JobSetup;
use App\Tests\Services\InvokableFactory\JobSetupInvokableFactory;
use App\Tests\Services\InvokableFactory\TestSetup;
use App\Tests\Services\InvokableFactory\TestSetupInvokableFactory;
use App\Tests\Services\InvokableHandler;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class JobStateFactoryTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private JobStateFactory $jobStateFactory;
    private InvokableHandler $invokableHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(InvokableInterface $setup, JobState $expectedState)
    {
        $this->invokableHandler->invoke($setup);

        self::assertEquals($expectedState, $this->jobStateFactory->create());
    }

    public function createDataProvider(): array
    {
        return [
            'compilation-awaiting: no job' => [
                'setup' => Invokable::createEmpty(),
                'expectedState' => new JobState(JobState::STATE_COMPILATION_AWAITING),
            ],
            'compilation-awaiting: has job, no sources' => [
                'setup' => JobSetupInvokableFactory::setup(),
                'expectedState' => new JobState(JobState::STATE_COMPILATION_AWAITING),
            ],
            'compilation-running: has job, has sources, no sources compiled' => [
                'setup' => JobSetupInvokableFactory::setup(
                    (new JobSetup())
                        ->withSources([
                            'Test/test1.yml',
                            'Test/test2.yml',
                        ])
                ),
                'expectedState' => new JobState(JobState::STATE_COMPILATION_RUNNING),
            ],
            'compilation-failed: has job, has sources, has more than zero compile-failure callbacks' => [
                'setup' => new InvokableCollection([
                    JobSetupInvokableFactory::setup(
                        (new JobSetup())
                            ->withSources([
                                'Test/test1.yml',
                                'Test/test2.yml',
                            ])
                    ),
                    CallbackSetupInvokableFactory::setup(
                        (new CallbackSetup())
                            ->withType(CallbackInterface::TYPE_COMPILE_FAILURE),
                    )
                ]),
                'expectedState' => new JobState(JobState::STATE_COMPILATION_FAILED),
            ],
            'execution-awaiting: compilation workflow complete and execution workflow not started' => [
                'setup' => new InvokableCollection([
                    JobSetupInvokableFactory::setup(
                        (new JobSetup())
                            ->withSources([
                                'Test/test1.yml',
                            ])
                    ),
                    TestSetupInvokableFactory::setup(
                        (new TestSetup())
                            ->withSource('/app/source/Test/test1.yml'),
                    )
                ]),
                'expectedState' => new JobState(JobState::STATE_EXECUTION_AWAITING),
            ],
            'execution-running' => [
                'setup' => new InvokableCollection([
                    JobSetupInvokableFactory::setup(
                        (new JobSetup())
                            ->withSources([
                                'Test/test1.yml',
                                'Test/test2.yml',
                            ])
                    ),
                    TestSetupInvokableFactory::setupCollection([
                        (new TestSetup())
                            ->withSource('/app/source/Test/test1.yml')
                            ->withState(Test::STATE_COMPLETE),
                        (new TestSetup())
                            ->withSource('/app/source/Test/test2.yml'),
                    ])
                ]),
                'expectedState' => new JobState(JobState::STATE_EXECUTION_RUNNING),
            ],
            'execution-complete' => [
                'setup' => new InvokableCollection([
                    JobSetupInvokableFactory::setup(
                        (new JobSetup())
                            ->withSources([
                                'Test/test1.yml',
                            ])
                    ),
                    TestSetupInvokableFactory::setupCollection([
                        (new TestSetup())
                            ->withSource('/app/source/Test/test1.yml')
                            ->withState(Test::STATE_COMPLETE),
                    ])
                ]),
                'expectedState' => new JobState(JobState::STATE_EXECUTION_COMPLETE),
            ],
            'execution-cancelled: test.state == failed > 0' => [
                'setup' => new InvokableCollection([
                    JobSetupInvokableFactory::setup(
                        (new JobSetup())
                            ->withSources([
                                'Test/test1.yml',
                            ])
                    ),
                    TestSetupInvokableFactory::setupCollection([
                        (new TestSetup())
                            ->withSource('/app/source/Test/test1.yml')
                            ->withState(Test::STATE_FAILED),
                    ])
                ]),
                'expectedState' => new JobState(JobState::STATE_EXECUTION_CANCELLED),
            ],
            'execution-cancelled: test.state == cancelled > 0' => [
                'setup' => new InvokableCollection([
                    JobSetupInvokableFactory::setup(
                        (new JobSetup())
                            ->withSources([
                                'Test/test1.yml',
                            ])
                    ),
                    TestSetupInvokableFactory::setupCollection([
                        (new TestSetup())
                            ->withSource('/app/source/Test/test1.yml')
                            ->withState(Test::STATE_CANCELLED),
                    ])
                ]),
                'expectedState' => new JobState(JobState::STATE_EXECUTION_CANCELLED),
            ],
        ];
    }
}
