<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\CallbackEntity;
use App\Model\Callback\CallbackModelInterface;
use App\Services\CallbackStateMutator;
use App\Services\CallbackStore;
use App\Tests\AbstractBaseFunctionalTest;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class CallbackStateMutatorTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private CallbackStateMutator $mutator;
    private CallbackEntity $callback;
    private CallbackStore $callbackStore;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();

        $this->callback = CallbackEntity::create(CallbackModelInterface::TYPE_COMPILE_FAILURE, []);
        $this->callbackStore->store($this->callback);
    }

    /**
     * @dataProvider setQueuedDataProvider
     *
     * @param CallbackModelInterface::STATE_* $initialState
     * @param CallbackModelInterface::STATE_* $expectedState
     */
    public function testSetQueued(string $initialState, string $expectedState)
    {
        $this->doSetAsStateTest($initialState, $expectedState, function () {
            $this->mutator->setQueued($this->callback);
        });
    }

    public function setQueuedDataProvider(): array
    {
        return [
            CallbackModelInterface::STATE_AWAITING => [
                'initialState' => CallbackModelInterface::STATE_AWAITING,
                'expectedState' => CallbackModelInterface::STATE_QUEUED,
            ],
            CallbackModelInterface::STATE_QUEUED => [
                'initialState' => CallbackModelInterface::STATE_QUEUED,
                'expectedState' => CallbackModelInterface::STATE_QUEUED,
            ],
            CallbackModelInterface::STATE_SENDING => [
                'initialState' => CallbackModelInterface::STATE_SENDING,
                'expectedState' => CallbackModelInterface::STATE_SENDING,
            ],
            CallbackModelInterface::STATE_FAILED => [
                'initialState' => CallbackModelInterface::STATE_FAILED,
                'expectedState' => CallbackModelInterface::STATE_FAILED,
            ],
            CallbackModelInterface::STATE_COMPLETE => [
                'initialState' => CallbackModelInterface::STATE_COMPLETE,
                'expectedState' => CallbackModelInterface::STATE_COMPLETE,
            ],
        ];
    }

    /**
     * @dataProvider setSendingDataProvider
     *
     * @param CallbackModelInterface::STATE_* $initialState
     * @param CallbackModelInterface::STATE_* $expectedState
     */
    public function testSetSending(string $initialState, string $expectedState)
    {
        $this->doSetAsStateTest($initialState, $expectedState, function () {
            $this->mutator->setSending($this->callback);
        });
    }

    public function setSendingDataProvider(): array
    {
        return [
            CallbackModelInterface::STATE_AWAITING => [
                'initialState' => CallbackModelInterface::STATE_AWAITING,
                'expectedState' => CallbackModelInterface::STATE_AWAITING,
            ],
            CallbackModelInterface::STATE_QUEUED => [
                'initialState' => CallbackModelInterface::STATE_QUEUED,
                'expectedState' => CallbackModelInterface::STATE_SENDING,
            ],
            CallbackModelInterface::STATE_SENDING => [
                'initialState' => CallbackModelInterface::STATE_SENDING,
                'expectedState' => CallbackModelInterface::STATE_SENDING,
            ],
            CallbackModelInterface::STATE_FAILED => [
                'initialState' => CallbackModelInterface::STATE_FAILED,
                'expectedState' => CallbackModelInterface::STATE_FAILED,
            ],
            CallbackModelInterface::STATE_COMPLETE => [
                'initialState' => CallbackModelInterface::STATE_COMPLETE,
                'expectedState' => CallbackModelInterface::STATE_COMPLETE,
            ],
        ];
    }

    /**
     * @dataProvider setFailedDataProvider
     *
     * @param CallbackModelInterface::STATE_* $initialState
     * @param CallbackModelInterface::STATE_* $expectedState
     */
    public function testSetFailed(string $initialState, string $expectedState)
    {
        $this->doSetAsStateTest($initialState, $expectedState, function () {
            $this->mutator->setFailed($this->callback);
        });
    }

    public function setFailedDataProvider(): array
    {
        return [
            CallbackModelInterface::STATE_AWAITING => [
                'initialState' => CallbackModelInterface::STATE_AWAITING,
                'expectedState' => CallbackModelInterface::STATE_AWAITING,
            ],
            CallbackModelInterface::STATE_QUEUED => [
                'initialState' => CallbackModelInterface::STATE_QUEUED,
                'expectedState' => CallbackModelInterface::STATE_QUEUED,
            ],
            CallbackModelInterface::STATE_SENDING => [
                'initialState' => CallbackModelInterface::STATE_SENDING,
                'expectedState' => CallbackModelInterface::STATE_FAILED,
            ],
            CallbackModelInterface::STATE_FAILED => [
                'initialState' => CallbackModelInterface::STATE_FAILED,
                'expectedState' => CallbackModelInterface::STATE_FAILED,
            ],
            CallbackModelInterface::STATE_COMPLETE => [
                'initialState' => CallbackModelInterface::STATE_COMPLETE,
                'expectedState' => CallbackModelInterface::STATE_COMPLETE,
            ],
        ];
    }

    /**
     * @dataProvider setCompleteDataProvider
     *
     * @param CallbackModelInterface::STATE_* $initialState
     * @param CallbackModelInterface::STATE_* $expectedState
     */
    public function testSetComplete(string $initialState, string $expectedState)
    {
        $this->doSetAsStateTest($initialState, $expectedState, function () {
            $this->mutator->setComplete($this->callback);
        });
    }

    public function setCompleteDataProvider(): array
    {
        return [
            CallbackModelInterface::STATE_AWAITING => [
                'initialState' => CallbackModelInterface::STATE_AWAITING,
                'expectedState' => CallbackModelInterface::STATE_AWAITING,
            ],
            CallbackModelInterface::STATE_QUEUED => [
                'initialState' => CallbackModelInterface::STATE_QUEUED,
                'expectedState' => CallbackModelInterface::STATE_QUEUED,
            ],
            CallbackModelInterface::STATE_SENDING => [
                'initialState' => CallbackModelInterface::STATE_SENDING,
                'expectedState' => CallbackModelInterface::STATE_COMPLETE,
            ],
            CallbackModelInterface::STATE_FAILED => [
                'initialState' => CallbackModelInterface::STATE_FAILED,
                'expectedState' => CallbackModelInterface::STATE_FAILED,
            ],
            CallbackModelInterface::STATE_COMPLETE => [
                'initialState' => CallbackModelInterface::STATE_COMPLETE,
                'expectedState' => CallbackModelInterface::STATE_COMPLETE,
            ],
        ];
    }

    /**
     * @dataProvider setSendingDataProvider
     *
     * @param CallbackModelInterface::STATE_* $initialState
     * @param CallbackModelInterface::STATE_* $expectedState
     * @param callable $setter
     */
    private function doSetAsStateTest(string $initialState, string $expectedState, callable $setter): void
    {
        $this->callback->setState($initialState);
        $this->callbackStore->store($this->callback);
        self::assertSame($initialState, $this->callback->getState());

        $setter();

        self::assertSame($expectedState, $this->callback->getState());
    }
}
