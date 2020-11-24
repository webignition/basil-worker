<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Callback\CallbackInterface;
use App\Entity\Test;
use App\Services\ApplicationState;
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

class ApplicationStateTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private ApplicationState $applicationState;
    private InvokableHandler $invokableHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    /**
     * @dataProvider isDataProvider
     *
     * @param InvokableInterface $setup
     * @param array<ApplicationState::STATE_*> $expectedIsStates
     * @param array<ApplicationState::STATE_*> $expectedIsNotStates
     */
    public function testIs(InvokableInterface $setup, array $expectedIsStates, array $expectedIsNotStates)
    {
        $this->invokableHandler->invoke($setup);

        self::assertTrue($this->applicationState->is(...$expectedIsStates));
        self::assertFalse($this->applicationState->is(...$expectedIsNotStates));
    }

    public function isDataProvider(): array
    {
        return [
            'no job, is awaiting' => [
                'setup' => Invokable::createEmpty(),
                'expectedIsStates' => [
                    ApplicationState::STATE_AWAITING_JOB,
                ],
                'expectedIsNotStates' => [
                    ApplicationState::STATE_AWAITING_SOURCES,
                    ApplicationState::STATE_COMPILING,
                    ApplicationState::STATE_EXECUTING,
                    ApplicationState::STATE_COMPLETING_CALLBACKS,
                    ApplicationState::STATE_COMPLETE,
                ],
            ],
            'has job, no sources' => [
                'setup' => JobSetupInvokableFactory::setup(),
                'expectedIsStates' => [
                    ApplicationState::STATE_AWAITING_SOURCES,
                ],
                'expectedIsNotStates' => [
                    ApplicationState::STATE_AWAITING_JOB,
                    ApplicationState::STATE_COMPILING,
                    ApplicationState::STATE_EXECUTING,
                    ApplicationState::STATE_COMPLETING_CALLBACKS,
                    ApplicationState::STATE_COMPLETE,
                ],
            ],
            'no sources compiled' => [
                'setup' => JobSetupInvokableFactory::setup(
                    (new JobSetup())
                        ->withSources([
                            'Test/test1.yml',
                            'Test/test2.yml',
                        ])
                ),
                'expectedIsStates' => [
                    ApplicationState::STATE_COMPILING,
                ],
                'expectedIsNotStates' => [
                    ApplicationState::STATE_AWAITING_JOB,
                    ApplicationState::STATE_AWAITING_SOURCES,
                    ApplicationState::STATE_EXECUTING,
                    ApplicationState::STATE_COMPLETING_CALLBACKS,
                    ApplicationState::STATE_COMPLETE,
                ],
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
                'expectedIsStates' => [
                    ApplicationState::STATE_COMPILING,
                ],
                'expectedIsNotStates' => [
                    ApplicationState::STATE_AWAITING_JOB,
                    ApplicationState::STATE_AWAITING_SOURCES,
                    ApplicationState::STATE_EXECUTING,
                    ApplicationState::STATE_COMPLETING_CALLBACKS,
                    ApplicationState::STATE_COMPLETE,
                ],
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
                'expectedIsStates' => [
                    ApplicationState::STATE_EXECUTING,
                ],
                'expectedIsNotStates' => [
                    ApplicationState::STATE_AWAITING_JOB,
                    ApplicationState::STATE_AWAITING_SOURCES,
                    ApplicationState::STATE_COMPILING,
                    ApplicationState::STATE_COMPLETING_CALLBACKS,
                    ApplicationState::STATE_COMPLETE,
                ],
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
                'expectedIsStates' => [
                    ApplicationState::STATE_EXECUTING,
                ],
                'expectedIsNotStates' => [
                    ApplicationState::STATE_AWAITING_JOB,
                    ApplicationState::STATE_AWAITING_SOURCES,
                    ApplicationState::STATE_COMPILING,
                    ApplicationState::STATE_COMPLETING_CALLBACKS,
                    ApplicationState::STATE_COMPLETE,
                ],
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
                'expectedIsStates' => [
                    ApplicationState::STATE_EXECUTING,
                ],
                'expectedIsNotStates' => [
                    ApplicationState::STATE_AWAITING_JOB,
                    ApplicationState::STATE_AWAITING_SOURCES,
                    ApplicationState::STATE_COMPILING,
                    ApplicationState::STATE_COMPLETING_CALLBACKS,
                    ApplicationState::STATE_COMPLETE,
                ],
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
                'expectedIsStates' => [
                    ApplicationState::STATE_COMPLETING_CALLBACKS,
                ],
                'expectedIsNotStates' => [
                    ApplicationState::STATE_AWAITING_JOB,
                    ApplicationState::STATE_AWAITING_SOURCES,
                    ApplicationState::STATE_COMPILING,
                    ApplicationState::STATE_EXECUTING,
                    ApplicationState::STATE_COMPLETE,
                ],
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
                'expectedIsStates' => [
                    ApplicationState::STATE_COMPLETE,
                ],
                'expectedIsNotStates' => [
                    ApplicationState::STATE_AWAITING_JOB,
                    ApplicationState::STATE_AWAITING_SOURCES,
                    ApplicationState::STATE_COMPILING,
                    ApplicationState::STATE_EXECUTING,
                    ApplicationState::STATE_COMPLETING_CALLBACKS,
                ],
            ],
        ];
    }
}
