<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Callback\CallbackInterface;
use App\Services\CompilationStateFactory;
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

class CompilationStateFactoryTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private CompilationStateFactory $compilationStateFactory;
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
     * @param array<CompilationStateFactory::STATE_*> $expectedIsStates
     * @param array<CompilationStateFactory::STATE_*> $expectedIsNotStates
     */
    public function testIs(InvokableInterface $setup, array $expectedIsStates, array $expectedIsNotStates)
    {
        $this->invokableHandler->invoke($setup);

        self::assertTrue($this->compilationStateFactory->is(...$expectedIsStates));
        self::assertFalse($this->compilationStateFactory->is(...$expectedIsNotStates));
    }

    public function isDataProvider(): array
    {
        return [
            'awaiting: no job' => [
                'setup' => Invokable::createEmpty(),
                'expectedIsStates' => [
                    CompilationStateFactory::STATE_AWAITING,
                ],
                'expectedIsNotStates' => [
                    CompilationStateFactory::STATE_RUNNING,
                    CompilationStateFactory::STATE_FAILED,
                    CompilationStateFactory::STATE_COMPLETE,
                    CompilationStateFactory::STATE_UNKNOWN,
                ],
            ],
            'awaiting: has job, no sources' => [
                'setup' => JobSetupInvokableFactory::setup(),
                'expectedIsStates' => [
                    CompilationStateFactory::STATE_AWAITING,
                ],
                'expectedIsNotStates' => [
                    CompilationStateFactory::STATE_RUNNING,
                    CompilationStateFactory::STATE_FAILED,
                    CompilationStateFactory::STATE_COMPLETE,
                    CompilationStateFactory::STATE_UNKNOWN,
                ],
            ],
            'running: has job, has sources, no sources compiled' => [
                'setup' => JobSetupInvokableFactory::setup(
                    (new JobSetup())
                        ->withSources([
                            'Test/test1.yml',
                            'Test/test2.yml',
                        ])
                ),
                'expectedIsStates' => [
                    CompilationStateFactory::STATE_RUNNING,
                ],
                'expectedIsNotStates' => [
                    CompilationStateFactory::STATE_AWAITING,
                    CompilationStateFactory::STATE_FAILED,
                    CompilationStateFactory::STATE_COMPLETE,
                    CompilationStateFactory::STATE_UNKNOWN,
                ],
            ],
            'failed: has job, has sources, has more than zero compile-failure callbacks' => [
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
                'expectedIsStates' => [
                    CompilationStateFactory::STATE_FAILED,
                ],
                'expectedIsNotStates' => [
                    CompilationStateFactory::STATE_AWAITING,
                    CompilationStateFactory::STATE_RUNNING,
                    CompilationStateFactory::STATE_COMPLETE,
                    CompilationStateFactory::STATE_UNKNOWN,
                ],
            ],
            'complete: has job, has sources, no next source' => [
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
                            ->withSource('/app/source/Test/test1.yml'),
                        (new TestSetup())
                            ->withSource('/app/source/Test/test2.yml'),
                    ])
                ]),
                'expectedIsStates' => [
                    CompilationStateFactory::STATE_COMPLETE,
                ],
                'expectedIsNotStates' => [
                    CompilationStateFactory::STATE_AWAITING,
                    CompilationStateFactory::STATE_RUNNING,
                    CompilationStateFactory::STATE_FAILED,
                    CompilationStateFactory::STATE_UNKNOWN,
                ],
            ],
        ];
    }
}
