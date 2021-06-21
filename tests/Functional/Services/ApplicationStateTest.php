<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Services\ApplicationState;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Model\CallbackSetup;
use App\Tests\Model\EnvironmentSetup;
use App\Tests\Model\JobSetup;
use App\Tests\Model\SourceSetup;
use App\Tests\Model\TestSetup;
use App\Tests\Services\EnvironmentFactory;
use App\Entity\Callback\CallbackInterface;
use App\Entity\Test;

class ApplicationStateTest extends AbstractBaseFunctionalTest
{
    private ApplicationState $applicationState;
    private EnvironmentFactory $environmentFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $applicationState = self::$container->get(ApplicationState::class);
        \assert($applicationState instanceof ApplicationState);
        $this->applicationState = $applicationState;

        $environmentFactory = self::$container->get(EnvironmentFactory::class);
        \assert($environmentFactory instanceof EnvironmentFactory);
        $this->environmentFactory = $environmentFactory;
    }

    /**
     * @dataProvider getDataProvider
     */
    public function testGet(EnvironmentSetup $setup, string $expectedState): void
    {
        $this->environmentFactory->create($setup);

        self::assertSame($expectedState, (string) $this->applicationState);
    }

    /**
     * @return array<mixed>
     */
    public function getDataProvider(): array
    {
        return [
            'no job, is awaiting' => [
                'setup' => new EnvironmentSetup(),
                'expectedState' => ApplicationState::STATE_AWAITING_JOB,
            ],
            'has job, no sources' => [
                'setup' => (new EnvironmentSetup())
                    ->withJobSetup(new JobSetup()),
                'expectedState' => ApplicationState::STATE_AWAITING_SOURCES,
            ],
            'no sources compiled' => [
                'setup' => (new EnvironmentSetup())
                    ->withJobSetup(new JobSetup())
                    ->withSourceSetups([
                        (new SourceSetup())->withPath('Test/test1.yml'),
                        (new SourceSetup())->withPath('Test/test2.yml'),
                    ]),
                'expectedState' => ApplicationState::STATE_COMPILING,
            ],
            'first source compiled' => [
                'setup' => (new EnvironmentSetup())
                    ->withJobSetup(new JobSetup())
                    ->withSourceSetups([
                        (new SourceSetup())->withPath('Test/test1.yml'),
                        (new SourceSetup())->withPath('Test/test2.yml'),
                    ])
                    ->withTestSetups([
                        (new TestSetup())->withSource('{{ compiler_source_directory }}/Test/test1.yml'),
                    ]),
                'expectedState' => ApplicationState::STATE_COMPILING,
            ],
            'all sources compiled, no tests running' => [
                'setup' => (new EnvironmentSetup())
                    ->withJobSetup(new JobSetup())
                    ->withSourceSetups([
                        (new SourceSetup())->withPath('Test/test1.yml'),
                        (new SourceSetup())->withPath('Test/test2.yml'),
                    ])
                    ->withTestSetups([
                        (new TestSetup())->withSource('{{ compiler_source_directory }}/Test/test1.yml'),
                        (new TestSetup())->withSource('{{ compiler_source_directory }}/Test/test2.yml'),
                    ]),
                'expectedState' => ApplicationState::STATE_EXECUTING,
            ],
            'first test complete, no callbacks' => [
                'setup' => (new EnvironmentSetup())
                    ->withJobSetup(new JobSetup())
                    ->withSourceSetups([
                        (new SourceSetup())->withPath('Test/test1.yml'),
                        (new SourceSetup())->withPath('Test/test2.yml'),
                    ])
                    ->withTestSetups([
                        (new TestSetup())
                            ->withSource('{{ compiler_source_directory }}/Test/test1.yml')
                            ->withState(Test::STATE_COMPLETE),
                        (new TestSetup())->withSource('{{ compiler_source_directory }}/Test/test2.yml'),
                    ]),
                'expectedState' => ApplicationState::STATE_EXECUTING,
            ],
            'first test complete, callback for first test complete' => [
                'setup' => (new EnvironmentSetup())
                    ->withJobSetup(new JobSetup())
                    ->withSourceSetups([
                        (new SourceSetup())->withPath('Test/test1.yml'),
                        (new SourceSetup())->withPath('Test/test2.yml'),
                    ])
                    ->withTestSetups([
                        (new TestSetup())
                            ->withSource('{{ compiler_source_directory }}/Test/test1.yml')
                            ->withState(Test::STATE_COMPLETE),
                        (new TestSetup())->withSource('{{ compiler_source_directory }}/Test/test2.yml'),
                    ])
                    ->withCallbackSetups([
                        (new CallbackSetup())->withState(CallbackInterface::STATE_COMPLETE),
                    ]),
                'expectedState' => ApplicationState::STATE_EXECUTING,
            ],
            'all tests complete, first callback complete, second callback running' => [
                'setup' => (new EnvironmentSetup())
                    ->withJobSetup(new JobSetup())
                    ->withSourceSetups([
                        (new SourceSetup())->withPath('Test/test1.yml'),
                        (new SourceSetup())->withPath('Test/test2.yml'),
                    ])
                    ->withTestSetups([
                        (new TestSetup())
                            ->withSource('{{ compiler_source_directory }}/Test/test1.yml')
                            ->withState(Test::STATE_COMPLETE),
                        (new TestSetup())->withSource('{{ compiler_source_directory }}/Test/test2.yml')
                            ->withState(Test::STATE_COMPLETE),
                    ])
                    ->withCallbackSetups([
                        (new CallbackSetup())->withState(CallbackInterface::STATE_COMPLETE),
                        (new CallbackSetup())->withState(CallbackInterface::STATE_SENDING)
                    ]),
                'expectedState' => ApplicationState::STATE_COMPLETING_CALLBACKS,
            ],
            'all tests complete, all callbacks complete' => [
                'setup' => (new EnvironmentSetup())
                    ->withJobSetup(new JobSetup())
                    ->withSourceSetups([
                        (new SourceSetup())->withPath('Test/test1.yml'),
                        (new SourceSetup())->withPath('Test/test2.yml'),
                    ])
                    ->withTestSetups([
                        (new TestSetup())
                            ->withSource('{{ compiler_source_directory }}/Test/test1.yml')
                            ->withState(Test::STATE_COMPLETE),
                        (new TestSetup())->withSource('{{ compiler_source_directory }}/Test/test2.yml')
                            ->withState(Test::STATE_COMPLETE),
                    ])
                    ->withCallbackSetups([
                        (new CallbackSetup())->withState(CallbackInterface::STATE_COMPLETE),
                        (new CallbackSetup())->withState(CallbackInterface::STATE_COMPLETE)
                    ]),
                'expectedState' => ApplicationState::STATE_COMPLETE,
            ],
            'has a job-timeout callback' => [
                'setup' => (new EnvironmentSetup())
                    ->withJobSetup(new JobSetup())
                    ->withCallbackSetups([
                        (new CallbackSetup())
                            ->withType(CallbackInterface::TYPE_JOB_TIME_OUT)
                            ->withState(CallbackInterface::STATE_COMPLETE),
                    ]),
                'expectedState' => ApplicationState::STATE_TIMED_OUT,
            ],
        ];
    }

    /**
     * @dataProvider isDataProvider
     *
     * @param array<ApplicationState::STATE_*> $expectedIsStates
     * @param array<ApplicationState::STATE_*> $expectedIsNotStates
     */
    public function testIs(
        EnvironmentSetup $setup,
        array $expectedIsStates,
        array $expectedIsNotStates
    ): void {
        $this->environmentFactory->create($setup);

        self::assertTrue($this->applicationState->is(...$expectedIsStates));
        self::assertFalse($this->applicationState->is(...$expectedIsNotStates));
    }

    /**
     * @return array<mixed>
     */
    public function isDataProvider(): array
    {
        return [
            'no job, is awaiting' => [
                'setup' => new EnvironmentSetup(),
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
                'setup' => (new EnvironmentSetup())
                    ->withJobSetup(new JobSetup()),
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
                'setup' => (new EnvironmentSetup())
                    ->withJobSetup(new JobSetup())
                    ->withSourceSetups([
                        (new SourceSetup())->withPath('Test/test1.yml'),
                        (new SourceSetup())->withPath('Test/test2.yml'),
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
                'setup' => (new EnvironmentSetup())
                    ->withJobSetup(new JobSetup())
                    ->withSourceSetups([
                        (new SourceSetup())->withPath('Test/test1.yml'),
                        (new SourceSetup())->withPath('Test/test2.yml'),
                    ])
                    ->withTestSetups([
                        (new TestSetup())->withSource('{{ compiler_source_directory }}/Test/test1.yml'),
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
                'setup' => (new EnvironmentSetup())
                    ->withJobSetup(new JobSetup())
                    ->withSourceSetups([
                        (new SourceSetup())->withPath('Test/test1.yml'),
                        (new SourceSetup())->withPath('Test/test2.yml'),
                    ])
                    ->withTestSetups([
                        (new TestSetup())->withSource('{{ compiler_source_directory }}/Test/test1.yml'),
                        (new TestSetup())->withSource('{{ compiler_source_directory }}/Test/test2.yml'),
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
                'setup' => (new EnvironmentSetup())
                    ->withJobSetup(new JobSetup())
                    ->withSourceSetups([
                        (new SourceSetup())->withPath('Test/test1.yml'),
                        (new SourceSetup())->withPath('Test/test2.yml'),
                    ])
                    ->withTestSetups([
                        (new TestSetup())
                            ->withSource('{{ compiler_source_directory }}/Test/test1.yml')
                            ->withState(Test::STATE_COMPLETE),
                        (new TestSetup())->withSource('{{ compiler_source_directory }}/Test/test2.yml'),
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
                'setup' => (new EnvironmentSetup())
                    ->withJobSetup(new JobSetup())
                    ->withSourceSetups([
                        (new SourceSetup())->withPath('Test/test1.yml'),
                        (new SourceSetup())->withPath('Test/test2.yml'),
                    ])
                    ->withTestSetups([
                        (new TestSetup())
                            ->withSource('{{ compiler_source_directory }}/Test/test1.yml')
                            ->withState(Test::STATE_COMPLETE),
                        (new TestSetup())->withSource('{{ compiler_source_directory }}/Test/test2.yml'),
                    ])
                    ->withCallbackSetups([
                        (new CallbackSetup())->withState(CallbackInterface::STATE_COMPLETE),
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
                'setup' => (new EnvironmentSetup())
                    ->withJobSetup(new JobSetup())
                    ->withSourceSetups([
                        (new SourceSetup())->withPath('Test/test1.yml'),
                        (new SourceSetup())->withPath('Test/test2.yml'),
                    ])
                    ->withTestSetups([
                        (new TestSetup())
                            ->withSource('{{ compiler_source_directory }}/Test/test1.yml')
                            ->withState(Test::STATE_COMPLETE),
                        (new TestSetup())->withSource('{{ compiler_source_directory }}/Test/test2.yml')
                            ->withState(Test::STATE_COMPLETE),
                    ])
                    ->withCallbackSetups([
                        (new CallbackSetup())->withState(CallbackInterface::STATE_COMPLETE),
                        (new CallbackSetup())->withState(CallbackInterface::STATE_SENDING)
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
                'setup' => (new EnvironmentSetup())
                    ->withJobSetup(new JobSetup())
                    ->withSourceSetups([
                        (new SourceSetup())->withPath('Test/test1.yml'),
                        (new SourceSetup())->withPath('Test/test2.yml'),
                    ])
                    ->withTestSetups([
                        (new TestSetup())
                            ->withSource('{{ compiler_source_directory }}/Test/test1.yml')
                            ->withState(Test::STATE_COMPLETE),
                        (new TestSetup())->withSource('{{ compiler_source_directory }}/Test/test2.yml')
                            ->withState(Test::STATE_COMPLETE),
                    ])
                    ->withCallbackSetups([
                        (new CallbackSetup())->withState(CallbackInterface::STATE_COMPLETE),
                        (new CallbackSetup())->withState(CallbackInterface::STATE_COMPLETE)
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
                'setup' => (new EnvironmentSetup())
                    ->withJobSetup(new JobSetup())
                    ->withCallbackSetups([
                        (new CallbackSetup())
                            ->withType(CallbackInterface::TYPE_JOB_TIME_OUT)
                            ->withState(CallbackInterface::STATE_COMPLETE),
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
