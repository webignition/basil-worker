<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Test;
use App\Model\ExecutionState;
use App\Services\ExecutionStateFactory;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Services\InvokableFactory\TestSetup;
use App\Tests\Services\InvokableFactory\TestSetupInvokableFactory;
use App\Tests\Services\InvokableHandler;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class ExecutionStateFactoryTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private ExecutionStateFactory $executionStateFactory;
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
     * @param array<ExecutionState::STATE_*> $expectedIsStates
     * @param array<ExecutionState::STATE_*> $expectedIsNotStates
     */
    public function testIs(InvokableInterface $setup, array $expectedIsStates, array $expectedIsNotStates)
    {
        $this->invokableHandler->invoke($setup);

        self::assertTrue($this->executionStateFactory->is(...$expectedIsStates));
        self::assertFalse($this->executionStateFactory->is(...$expectedIsNotStates));
    }

    public function isDataProvider(): array
    {
        return [
            'awaiting: not has finished tests and not has running tests and not has awaiting tests' => [
                'setup' => Invokable::createEmpty(),
                'expectedIsStates' => [
                    ExecutionState::STATE_AWAITING,
                ],
                'expectedIsNotStates' => [
                    ExecutionState::STATE_RUNNING,
                    ExecutionState::STATE_COMPLETE,
                    ExecutionState::STATE_CANCELLED,
                ],
            ],
            'running: not has finished tests and has running tests and not has awaiting tests' => [
                'setup' => TestSetupInvokableFactory::setupCollection([
                    (new TestSetup())->withState(Test::STATE_RUNNING),
                ]),
                'expectedIsStates' => [
                    ExecutionState::STATE_RUNNING,
                ],
                'expectedIsNotStates' => [
                    ExecutionState::STATE_AWAITING,
                    ExecutionState::STATE_COMPLETE,
                    ExecutionState::STATE_CANCELLED,
                ],
            ],
            'awaiting: not has finished tests and not has running tests and has awaiting tests' => [
                'setup' => TestSetupInvokableFactory::setupCollection([
                    (new TestSetup())->withState(Test::STATE_AWAITING),
                ]),
                'expectedIsStates' => [
                    ExecutionState::STATE_AWAITING,
                ],
                'expectedIsNotStates' => [
                    ExecutionState::STATE_RUNNING,
                    ExecutionState::STATE_COMPLETE,
                    ExecutionState::STATE_CANCELLED,
                ],
            ],
            'running: has complete tests and has running tests and not has awaiting tests' => [
                'setup' => TestSetupInvokableFactory::setupCollection([
                    (new TestSetup())->withState(Test::STATE_COMPLETE),
                    (new TestSetup())->withState(Test::STATE_RUNNING),
                ]),
                'expectedIsStates' => [
                    ExecutionState::STATE_RUNNING,
                ],
                'expectedIsNotStates' => [
                    ExecutionState::STATE_AWAITING,
                    ExecutionState::STATE_COMPLETE,
                    ExecutionState::STATE_CANCELLED,
                ],
            ],
            'running: has complete tests and not has running tests and has awaiting tests' => [
                'setup' => TestSetupInvokableFactory::setupCollection([
                    (new TestSetup())->withState(Test::STATE_COMPLETE),
                    (new TestSetup())->withState(Test::STATE_AWAITING),
                ]),
                'expectedIsStates' => [
                    ExecutionState::STATE_RUNNING,
                ],
                'expectedIsNotStates' => [
                    ExecutionState::STATE_AWAITING,
                    ExecutionState::STATE_COMPLETE,
                    ExecutionState::STATE_CANCELLED,
                ],
            ],
            'complete: has finished tests and not has running tests and not has awaiting tests' => [
                'setup' => TestSetupInvokableFactory::setupCollection([
                    (new TestSetup())->withState(Test::STATE_COMPLETE),
                ]),
                'expectedIsStates' => [
                    ExecutionState::STATE_COMPLETE,
                ],
                'expectedIsNotStates' => [
                    ExecutionState::STATE_AWAITING,
                    ExecutionState::STATE_RUNNING,
                    ExecutionState::STATE_CANCELLED,
                ],
            ],
            'cancelled: has failed tests' => [
                'setup' => TestSetupInvokableFactory::setupCollection([
                    (new TestSetup())->withState(Test::STATE_FAILED),
                ]),
                'expectedIsStates' => [
                    ExecutionState::STATE_CANCELLED,
                ],
                'expectedIsNotStates' => [
                    ExecutionState::STATE_AWAITING,
                    ExecutionState::STATE_RUNNING,
                    ExecutionState::STATE_COMPLETE,
                ],
            ],
            'cancelled: has cancelled tests' => [
                'setup' => TestSetupInvokableFactory::setupCollection([
                    (new TestSetup())->withState(Test::STATE_CANCELLED),
                ]),
                'expectedIsStates' => [
                    ExecutionState::STATE_CANCELLED,
                ],
                'expectedIsNotStates' => [
                    ExecutionState::STATE_AWAITING,
                    ExecutionState::STATE_RUNNING,
                    ExecutionState::STATE_COMPLETE,
                ],
            ],
        ];
    }
}
