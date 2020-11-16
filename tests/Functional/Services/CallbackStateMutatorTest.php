<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Callback\CallbackEntity;
use App\Entity\Callback\CallbackInterface;
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

        $this->callback = CallbackEntity::create(CallbackInterface::TYPE_COMPILE_FAILURE, []);
        $this->callbackStore->store($this->callback);
    }

    /**
     * @dataProvider setQueuedDataProvider
     *
     * @param CallbackInterface::STATE_* $initialState
     * @param CallbackInterface::STATE_* $expectedState
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
            CallbackInterface::STATE_AWAITING => [
                'initialState' => CallbackInterface::STATE_AWAITING,
                'expectedState' => CallbackInterface::STATE_QUEUED,
            ],
            CallbackInterface::STATE_QUEUED => [
                'initialState' => CallbackInterface::STATE_QUEUED,
                'expectedState' => CallbackInterface::STATE_QUEUED,
            ],
            CallbackInterface::STATE_SENDING => [
                'initialState' => CallbackInterface::STATE_SENDING,
                'expectedState' => CallbackInterface::STATE_SENDING,
            ],
            CallbackInterface::STATE_FAILED => [
                'initialState' => CallbackInterface::STATE_FAILED,
                'expectedState' => CallbackInterface::STATE_FAILED,
            ],
            CallbackInterface::STATE_COMPLETE => [
                'initialState' => CallbackInterface::STATE_COMPLETE,
                'expectedState' => CallbackInterface::STATE_COMPLETE,
            ],
        ];
    }

    /**
     * @dataProvider setSendingDataProvider
     *
     * @param CallbackInterface::STATE_* $initialState
     * @param CallbackInterface::STATE_* $expectedState
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
            CallbackInterface::STATE_AWAITING => [
                'initialState' => CallbackInterface::STATE_AWAITING,
                'expectedState' => CallbackInterface::STATE_AWAITING,
            ],
            CallbackInterface::STATE_QUEUED => [
                'initialState' => CallbackInterface::STATE_QUEUED,
                'expectedState' => CallbackInterface::STATE_SENDING,
            ],
            CallbackInterface::STATE_SENDING => [
                'initialState' => CallbackInterface::STATE_SENDING,
                'expectedState' => CallbackInterface::STATE_SENDING,
            ],
            CallbackInterface::STATE_FAILED => [
                'initialState' => CallbackInterface::STATE_FAILED,
                'expectedState' => CallbackInterface::STATE_FAILED,
            ],
            CallbackInterface::STATE_COMPLETE => [
                'initialState' => CallbackInterface::STATE_COMPLETE,
                'expectedState' => CallbackInterface::STATE_COMPLETE,
            ],
        ];
    }

    /**
     * @dataProvider setFailedDataProvider
     *
     * @param CallbackInterface::STATE_* $initialState
     * @param CallbackInterface::STATE_* $expectedState
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
            CallbackInterface::STATE_AWAITING => [
                'initialState' => CallbackInterface::STATE_AWAITING,
                'expectedState' => CallbackInterface::STATE_AWAITING,
            ],
            CallbackInterface::STATE_QUEUED => [
                'initialState' => CallbackInterface::STATE_QUEUED,
                'expectedState' => CallbackInterface::STATE_QUEUED,
            ],
            CallbackInterface::STATE_SENDING => [
                'initialState' => CallbackInterface::STATE_SENDING,
                'expectedState' => CallbackInterface::STATE_FAILED,
            ],
            CallbackInterface::STATE_FAILED => [
                'initialState' => CallbackInterface::STATE_FAILED,
                'expectedState' => CallbackInterface::STATE_FAILED,
            ],
            CallbackInterface::STATE_COMPLETE => [
                'initialState' => CallbackInterface::STATE_COMPLETE,
                'expectedState' => CallbackInterface::STATE_COMPLETE,
            ],
        ];
    }

    /**
     * @dataProvider setCompleteDataProvider
     *
     * @param CallbackInterface::STATE_* $initialState
     * @param CallbackInterface::STATE_* $expectedState
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
            CallbackInterface::STATE_AWAITING => [
                'initialState' => CallbackInterface::STATE_AWAITING,
                'expectedState' => CallbackInterface::STATE_AWAITING,
            ],
            CallbackInterface::STATE_QUEUED => [
                'initialState' => CallbackInterface::STATE_QUEUED,
                'expectedState' => CallbackInterface::STATE_QUEUED,
            ],
            CallbackInterface::STATE_SENDING => [
                'initialState' => CallbackInterface::STATE_SENDING,
                'expectedState' => CallbackInterface::STATE_COMPLETE,
            ],
            CallbackInterface::STATE_FAILED => [
                'initialState' => CallbackInterface::STATE_FAILED,
                'expectedState' => CallbackInterface::STATE_FAILED,
            ],
            CallbackInterface::STATE_COMPLETE => [
                'initialState' => CallbackInterface::STATE_COMPLETE,
                'expectedState' => CallbackInterface::STATE_COMPLETE,
            ],
        ];
    }

    /**
     * @dataProvider setSendingDataProvider
     *
     * @param CallbackInterface::STATE_* $initialState
     * @param CallbackInterface::STATE_* $expectedState
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
