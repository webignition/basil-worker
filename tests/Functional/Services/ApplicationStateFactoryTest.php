<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Callback\CallbackInterface;
use App\Entity\Test;
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
     * @dataProvider isDataProvider
     *
     * @param InvokableInterface $setup
     * @param array<ApplicationStateFactory::STATE_*> $expectedIsStates
     * @param array<ApplicationStateFactory::STATE_*> $expectedIsNotStates
     */
    public function testIs(InvokableInterface $setup, array $expectedIsStates, array $expectedIsNotStates)
    {
        $this->invokableHandler->invoke($setup);

        self::assertTrue($this->applicationStateFactory->is(...$expectedIsStates));
        self::assertFalse($this->applicationStateFactory->is(...$expectedIsNotStates));
    }

    public function isDataProvider(): array
    {
        return [
            'no job, is awaiting' => [
                'setup' => Invokable::createEmpty(),
                'expectedIsStates' => [
                    ApplicationStateFactory::STATE_AWAITING_JOB,
                ],
                'expectedIsNotStates' => [
                    ApplicationStateFactory::STATE_AWAITING_SOURCES,
                    ApplicationStateFactory::STATE_COMPILING,
                    ApplicationStateFactory::STATE_EXECUTING,
                    ApplicationStateFactory::STATE_COMPLETING_CALLBACKS,
                    ApplicationStateFactory::STATE_COMPLETE,
                ],
            ],
            'has job, no sources' => [
                'setup' => JobSetupInvokableFactory::setup(),
                'expectedIsStates' => [
                    ApplicationStateFactory::STATE_AWAITING_SOURCES,
                ],
                'expectedIsNotStates' => [
                    ApplicationStateFactory::STATE_AWAITING_JOB,
                    ApplicationStateFactory::STATE_COMPILING,
                    ApplicationStateFactory::STATE_EXECUTING,
                    ApplicationStateFactory::STATE_COMPLETING_CALLBACKS,
                    ApplicationStateFactory::STATE_COMPLETE,
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
                    ApplicationStateFactory::STATE_COMPILING,
                ],
                'expectedIsNotStates' => [
                    ApplicationStateFactory::STATE_AWAITING_JOB,
                    ApplicationStateFactory::STATE_AWAITING_SOURCES,
                    ApplicationStateFactory::STATE_EXECUTING,
                    ApplicationStateFactory::STATE_COMPLETING_CALLBACKS,
                    ApplicationStateFactory::STATE_COMPLETE,
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
                    ApplicationStateFactory::STATE_COMPILING,
                ],
                'expectedIsNotStates' => [
                    ApplicationStateFactory::STATE_AWAITING_JOB,
                    ApplicationStateFactory::STATE_AWAITING_SOURCES,
                    ApplicationStateFactory::STATE_EXECUTING,
                    ApplicationStateFactory::STATE_COMPLETING_CALLBACKS,
                    ApplicationStateFactory::STATE_COMPLETE,
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
                    ApplicationStateFactory::STATE_EXECUTING,
                ],
                'expectedIsNotStates' => [
                    ApplicationStateFactory::STATE_AWAITING_JOB,
                    ApplicationStateFactory::STATE_AWAITING_SOURCES,
                    ApplicationStateFactory::STATE_COMPILING,
                    ApplicationStateFactory::STATE_COMPLETING_CALLBACKS,
                    ApplicationStateFactory::STATE_COMPLETE,
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
                    ApplicationStateFactory::STATE_EXECUTING,
                ],
                'expectedIsNotStates' => [
                    ApplicationStateFactory::STATE_AWAITING_JOB,
                    ApplicationStateFactory::STATE_AWAITING_SOURCES,
                    ApplicationStateFactory::STATE_COMPILING,
                    ApplicationStateFactory::STATE_COMPLETING_CALLBACKS,
                    ApplicationStateFactory::STATE_COMPLETE,
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
                    ApplicationStateFactory::STATE_EXECUTING,
                ],
                'expectedIsNotStates' => [
                    ApplicationStateFactory::STATE_AWAITING_JOB,
                    ApplicationStateFactory::STATE_AWAITING_SOURCES,
                    ApplicationStateFactory::STATE_COMPILING,
                    ApplicationStateFactory::STATE_COMPLETING_CALLBACKS,
                    ApplicationStateFactory::STATE_COMPLETE,
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
                    ApplicationStateFactory::STATE_COMPLETING_CALLBACKS,
                ],
                'expectedIsNotStates' => [
                    ApplicationStateFactory::STATE_AWAITING_JOB,
                    ApplicationStateFactory::STATE_AWAITING_SOURCES,
                    ApplicationStateFactory::STATE_COMPILING,
                    ApplicationStateFactory::STATE_EXECUTING,
                    ApplicationStateFactory::STATE_COMPLETE,
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
                    ApplicationStateFactory::STATE_COMPLETE,
                ],
                'expectedIsNotStates' => [
                    ApplicationStateFactory::STATE_AWAITING_JOB,
                    ApplicationStateFactory::STATE_AWAITING_SOURCES,
                    ApplicationStateFactory::STATE_COMPILING,
                    ApplicationStateFactory::STATE_EXECUTING,
                    ApplicationStateFactory::STATE_COMPLETING_CALLBACKS,
                ],
            ],
        ];
    }
}
