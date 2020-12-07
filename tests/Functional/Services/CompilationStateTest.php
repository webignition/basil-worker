<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Callback\CallbackInterface;
use App\Services\CompilationState;
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
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class CompilationStateTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private CompilationState $compilationState;
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
     * @param array<CompilationState::STATE_*> $expectedIsStates
     * @param array<CompilationState::STATE_*> $expectedIsNotStates
     */
    public function testIs(InvokableInterface $setup, array $expectedIsStates, array $expectedIsNotStates)
    {
        $this->invokableHandler->invoke($setup);

        self::assertTrue($this->compilationState->is(...$expectedIsStates));
        self::assertFalse($this->compilationState->is(...$expectedIsNotStates));
    }

    public function isDataProvider(): array
    {
        return [
            'awaiting: no job' => [
                'setup' => Invokable::createEmpty(),
                'expectedIsStates' => [
                    CompilationState::STATE_AWAITING,
                ],
                'expectedIsNotStates' => [
                    CompilationState::STATE_RUNNING,
                    CompilationState::STATE_FAILED,
                    CompilationState::STATE_COMPLETE,
                    CompilationState::STATE_UNKNOWN,
                ],
            ],
            'awaiting: has job, no sources' => [
                'setup' => JobSetupInvokableFactory::setup(),
                'expectedIsStates' => [
                    CompilationState::STATE_AWAITING,
                ],
                'expectedIsNotStates' => [
                    CompilationState::STATE_RUNNING,
                    CompilationState::STATE_FAILED,
                    CompilationState::STATE_COMPLETE,
                    CompilationState::STATE_UNKNOWN,
                ],
            ],
            'running: has job, has sources, no sources compiled' => [
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
                    CompilationState::STATE_RUNNING,
                ],
                'expectedIsNotStates' => [
                    CompilationState::STATE_AWAITING,
                    CompilationState::STATE_FAILED,
                    CompilationState::STATE_COMPLETE,
                    CompilationState::STATE_UNKNOWN,
                ],
            ],
            'failed: has job, has sources, has more than zero compile-failure callbacks' => [
                'setup' => new InvokableCollection([
                    JobSetupInvokableFactory::setup(),
                    CallbackSetupInvokableFactory::setup(
                        (new CallbackSetup())
                            ->withType(CallbackInterface::TYPE_COMPILE_FAILURE),
                    )
                ]),
                'expectedIsStates' => [
                    CompilationState::STATE_FAILED,
                ],
                'expectedIsNotStates' => [
                    CompilationState::STATE_AWAITING,
                    CompilationState::STATE_RUNNING,
                    CompilationState::STATE_COMPLETE,
                    CompilationState::STATE_UNKNOWN,
                ],
            ],
            'complete: has job, has sources, no next source' => [
                'setup' => new InvokableCollection([
                    JobSetupInvokableFactory::setup(),
                    TestSetupInvokableFactory::setupCollection([
                        (new TestSetup())
                            ->withSource('/app/source/Test/test1.yml'),
                        (new TestSetup())
                            ->withSource('/app/source/Test/test2.yml'),
                    ])
                ]),
                'expectedIsStates' => [
                    CompilationState::STATE_COMPLETE,
                ],
                'expectedIsNotStates' => [
                    CompilationState::STATE_AWAITING,
                    CompilationState::STATE_RUNNING,
                    CompilationState::STATE_FAILED,
                    CompilationState::STATE_UNKNOWN,
                ],
            ],
        ];
    }
}
