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
     * @dataProvider createDataProvider
     */
    public function testCreate(InvokableInterface $setup, ExecutionState $expectedState)
    {
        $this->invokableHandler->invoke($setup);

        self::assertEquals($expectedState, $this->executionStateFactory->create());
    }

    public function createDataProvider(): array
    {
        return [
            'awaiting: not has finished tests and not has running tests and not has awaiting tests' => [
                'setup' => Invokable::createEmpty(),
                'expectedState' => new ExecutionState(ExecutionState::STATE_AWAITING),
            ],
            'running: not has finished tests and has running tests and not has awaiting tests' => [
                'setup' => TestSetupInvokableFactory::setupCollection([
                    (new TestSetup())->withState(Test::STATE_RUNNING),
                ]),
                'expectedState' => new ExecutionState(ExecutionState::STATE_RUNNING),
            ],
            'awaiting: not has finished tests and not has running tests and has awaiting tests' => [
                'setup' => TestSetupInvokableFactory::setupCollection([
                    (new TestSetup())->withState(Test::STATE_AWAITING),
                ]),
                'expectedState' => new ExecutionState(ExecutionState::STATE_AWAITING),
            ],
            'running: has complete tests and has running tests and not has awaiting tests' => [
                'setup' => TestSetupInvokableFactory::setupCollection([
                    (new TestSetup())->withState(Test::STATE_COMPLETE),
                    (new TestSetup())->withState(Test::STATE_RUNNING),
                ]),
                'expectedState' => new ExecutionState(ExecutionState::STATE_RUNNING),
            ],
            'running: has complete tests and not has running tests and has awaiting tests' => [
                'setup' => TestSetupInvokableFactory::setupCollection([
                    (new TestSetup())->withState(Test::STATE_COMPLETE),
                    (new TestSetup())->withState(Test::STATE_AWAITING),
                ]),
                'expectedState' => new ExecutionState(ExecutionState::STATE_RUNNING),
            ],
            'complete: has finished tests and not has running tests and not has awaiting tests' => [
                'setup' => TestSetupInvokableFactory::setupCollection([
                    (new TestSetup())->withState(Test::STATE_COMPLETE),
                ]),
                'expectedState' => new ExecutionState(ExecutionState::STATE_COMPLETE),
            ],
            'cancelled: has failed tests' => [
                'setup' => TestSetupInvokableFactory::setupCollection([
                    (new TestSetup())->withState(Test::STATE_FAILED),
                ]),
                'expectedState' => new ExecutionState(ExecutionState::STATE_CANCELLED),
            ],
            'cancelled: has cancelled tests' => [
                'setup' => TestSetupInvokableFactory::setupCollection([
                    (new TestSetup())->withState(Test::STATE_CANCELLED),
                ]),
                'expectedState' => new ExecutionState(ExecutionState::STATE_CANCELLED),
            ],
        ];
    }
}
