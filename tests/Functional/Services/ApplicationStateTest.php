<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Services\ApplicationState;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableCollection;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Services\InvokableFactory\CallbackSetup;
use App\Tests\Services\InvokableFactory\CallbackSetupInvokableFactory;
use App\Tests\Services\InvokableFactory\JobSetupInvokableFactory;
use App\Tests\Services\InvokableFactory\SourceSetup;
use App\Tests\Services\InvokableFactory\SourceSetupInvokableFactory;
use App\Tests\Services\InvokableFactory\TestSetup;
use App\Tests\Services\InvokableFactory\TestSetupInvokableFactory;
use App\Tests\Services\InvokableHandler;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Test;
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
                    ApplicationState::STATE_TIMED_OUT,
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
                    ApplicationState::STATE_TIMED_OUT,
                ],
            ],
            'no sources compiled' => [
                'setup' => new InvokableCollection([
                    'create job' => JobSetupInvokableFactory::setup(),
                    'add job sources' => SourceSetupInvokableFactory::setupCollection([
                        (new SourceSetup())
                            ->withPath('Test/test1.yml'),
                        (new SourceSetup())
                            ->withPath('Test/test2.yml'),
                    ]),
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
                    ApplicationState::STATE_TIMED_OUT,
                ],
            ],
            'first source compiled' => [
                'setup' => new InvokableCollection([
                    'create job' => JobSetupInvokableFactory::setup(),
                    'add job sources' => SourceSetupInvokableFactory::setupCollection([
                        (new SourceSetup())
                            ->withPath('Test/test1.yml'),
                        (new SourceSetup())
                            ->withPath('Test/test2.yml'),
                    ]),
                    'create tests' => TestSetupInvokableFactory::setupCollection([
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
                    ApplicationState::STATE_TIMED_OUT,
                ],
            ],
            'all sources compiled, no tests running' => [
                'setup' => new InvokableCollection([
                    'create job' => JobSetupInvokableFactory::setup(),
                    'add job sources' => SourceSetupInvokableFactory::setupCollection([
                        (new SourceSetup())
                            ->withPath('Test/test1.yml'),
                        (new SourceSetup())
                            ->withPath('Test/test2.yml'),
                    ]),
                    'create tests' => TestSetupInvokableFactory::setupCollection([
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
                    ApplicationState::STATE_TIMED_OUT,
                ],
            ],
            'first test complete, no callbacks' => [
                'setup' => new InvokableCollection([
                    'create job' => JobSetupInvokableFactory::setup(),
                    'add job sources' => SourceSetupInvokableFactory::setupCollection([
                        (new SourceSetup())
                            ->withPath('Test/test1.yml'),
                        (new SourceSetup())
                            ->withPath('Test/test2.yml'),
                    ]),
                    'create tests' => TestSetupInvokableFactory::setupCollection([
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
                    ApplicationState::STATE_TIMED_OUT,
                ],
            ],
            'first test complete, callback for first test complete' => [
                'setup' => new InvokableCollection([
                    'create job' => JobSetupInvokableFactory::setup(),
                    'add job sources' => SourceSetupInvokableFactory::setupCollection([
                        (new SourceSetup())
                            ->withPath('Test/test1.yml'),
                        (new SourceSetup())
                            ->withPath('Test/test2.yml'),
                    ]),
                    'create tests' => TestSetupInvokableFactory::setupCollection([
                        (new TestSetup())
                            ->withSource('/app/source/Test/test1.yml')
                            ->withState(Test::STATE_COMPLETE),
                        (new TestSetup())->withSource('/app/source/Test/test2.yml'),
                    ]),
                    'create callback' => CallbackSetupInvokableFactory::setup(
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
                    ApplicationState::STATE_TIMED_OUT,
                ],
            ],
            'all tests complete, first callback complete, second callback running' => [
                'setup' => new InvokableCollection([
                    'create job' => JobSetupInvokableFactory::setup(),
                    'add job sources' => SourceSetupInvokableFactory::setupCollection([
                        (new SourceSetup())
                            ->withPath('Test/test1.yml'),
                        (new SourceSetup())
                            ->withPath('Test/test2.yml'),
                    ]),
                    'create tests' => TestSetupInvokableFactory::setupCollection([
                        (new TestSetup())
                            ->withSource('/app/source/Test/test1.yml')
                            ->withState(Test::STATE_COMPLETE),
                        (new TestSetup())
                            ->withSource('/app/source/Test/test2.yml')
                            ->withState(Test::STATE_COMPLETE),
                    ]),
                    'create callback 1' => CallbackSetupInvokableFactory::setup(
                        (new CallbackSetup())->withState(CallbackInterface::STATE_COMPLETE)
                    ),
                    'create callback 2' => CallbackSetupInvokableFactory::setup(
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
                    ApplicationState::STATE_TIMED_OUT,
                ],
            ],
            'all tests complete, all callbacks complete' => [
                'setup' => new InvokableCollection([
                    'create job' => JobSetupInvokableFactory::setup(),
                    'add job sources' => SourceSetupInvokableFactory::setupCollection([
                        (new SourceSetup())
                            ->withPath('Test/test1.yml'),
                        (new SourceSetup())
                            ->withPath('Test/test2.yml'),
                    ]),
                    'create tests' => TestSetupInvokableFactory::setupCollection([
                        (new TestSetup())
                            ->withSource('/app/source/Test/test1.yml')
                            ->withState(Test::STATE_COMPLETE),
                        (new TestSetup())
                            ->withSource('/app/source/Test/test2.yml')
                            ->withState(Test::STATE_COMPLETE),
                    ]),
                    'create callback 1' => CallbackSetupInvokableFactory::setup(
                        (new CallbackSetup())->withState(CallbackInterface::STATE_COMPLETE)
                    ),
                    'create callback 2' => CallbackSetupInvokableFactory::setup(
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
                    ApplicationState::STATE_TIMED_OUT,
                ],
            ],
            'has a job-timeout callback' => [
                'setup' => new InvokableCollection([
                    JobSetupInvokableFactory::setup(),
                    'create callback' => CallbackSetupInvokableFactory::setup(
                        (new CallbackSetup())
                            ->withType(CallbackInterface::TYPE_JOB_TIMEOUT)
                            ->withState(CallbackInterface::STATE_COMPLETE)
                    ),
                ]),
                'expectedIsStates' => [
                    ApplicationState::STATE_TIMED_OUT,
                ],
                'expectedIsNotStates' => [
                    ApplicationState::STATE_AWAITING_JOB,
                    ApplicationState::STATE_AWAITING_SOURCES,
                    ApplicationState::STATE_COMPILING,
                    ApplicationState::STATE_EXECUTING,
                    ApplicationState::STATE_COMPLETING_CALLBACKS,
                    ApplicationState::STATE_COMPLETE,
                ],
            ],
        ];
    }
}
