<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Callback\CallbackInterface;
use App\Entity\Test;
use App\Model\ApplicationState;
use App\Services\ApplicationStateFactory;
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

class ApplicationStateFactoryTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private ApplicationStateFactory $applicationStateFactory;
    private InvokableHandler $invokableHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    /**
     * @dataProvider createDataProvider
     *
     * @param InvokableInterface $setup
     * @param ApplicationState $expectedState
     */
    public function testCreate(InvokableInterface $setup, ApplicationState $expectedState)
    {
        $this->invokableHandler->invoke($setup);

        self::assertEquals($expectedState, $this->applicationStateFactory->create());
    }

    public function createDataProvider(): array
    {
        return [
            'no job' => [
                'setup' => Invokable::createEmpty(),
                'expectedState' => new ApplicationState(ApplicationState::STATE_AWAITING_JOB),
            ],
            'has job, no sources' => [
                'setup' => JobSetupInvokableFactory::setup(),
                'expectedState' => new ApplicationState(ApplicationState::STATE_AWAITING_SOURCES),
            ],
            'no sources compiled' => [
                'setup' => JobSetupInvokableFactory::setup(
                    (new JobSetup())
                        ->withSources([
                            'Test/test1.yml',
                            'Test/test2.yml',
                        ])
                ),
                'expectedState' => new ApplicationState(ApplicationState::STATE_COMPILING),
            ],
            'first source compiled' => [
                'setup' => new InvokableCollection([
                    JobSetupInvokableFactory::setup(
                        (new JobSetup())
                            ->withSources([
                                'Test/test1.yml',
                                'Test/test2.yml',
                            ])
                    ),
                    TestSetupInvokableFactory::setupCollection([
                        (new TestSetup())->withSource('/app/source/Test/test1.yml'),
                    ])
                ]),
                'expectedState' => new ApplicationState(ApplicationState::STATE_COMPILING),
            ],
            'all sources compiled, no tests running' => [
                'setup' => new InvokableCollection([
                    JobSetupInvokableFactory::setup(
                        (new JobSetup())
                            ->withSources([
                                'Test/test1.yml',
                                'Test/test2.yml',
                            ])
                    ),
                    TestSetupInvokableFactory::setupCollection([
                        (new TestSetup())->withSource('/app/source/Test/test1.yml'),
                        (new TestSetup())->withSource('/app/source/Test/test2.yml'),
                    ])
                ]),
                'expectedState' => new ApplicationState(ApplicationState::STATE_EXECUTING),
            ],
            'first test complete, no callbacks' => [
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
                        (new TestSetup())->withSource('/app/source/Test/test2.yml'),
                    ])
                ]),
                'expectedState' => new ApplicationState(ApplicationState::STATE_EXECUTING),
            ],
            'first test complete, callback for first test complete' => [
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
                        (new TestSetup())->withSource('/app/source/Test/test2.yml'),
                    ]),
                    CallbackSetupInvokableFactory::setup(
                        (new CallbackSetup())->withState(CallbackInterface::STATE_COMPLETE)
                    ),
                ]),
                'expectedState' => new ApplicationState(ApplicationState::STATE_EXECUTING),
            ],
            'all tests complete, first callback complete, second callback running' => [
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
                            ->withSource('/app/source/Test/test2.yml')
                            ->withState(Test::STATE_COMPLETE),
                    ]),
                    CallbackSetupInvokableFactory::setup(
                        (new CallbackSetup())->withState(CallbackInterface::STATE_COMPLETE)
                    ),
                    CallbackSetupInvokableFactory::setup(
                        (new CallbackSetup())->withState(CallbackInterface::STATE_SENDING)
                    ),
                ]),
                'expectedState' => new ApplicationState(ApplicationState::STATE_COMPLETING_CALLBACKS),
            ],
            'all tests complete, all callbacks complete' => [
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
                            ->withSource('/app/source/Test/test2.yml')
                            ->withState(Test::STATE_COMPLETE),
                    ]),
                    CallbackSetupInvokableFactory::setup(
                        (new CallbackSetup())->withState(CallbackInterface::STATE_COMPLETE)
                    ),
                    CallbackSetupInvokableFactory::setup(
                        (new CallbackSetup())->withState(CallbackInterface::STATE_COMPLETE)
                    ),
                ]),
                'expectedState' => new ApplicationState(ApplicationState::STATE_COMPLETE),
            ],
        ];
    }
}
