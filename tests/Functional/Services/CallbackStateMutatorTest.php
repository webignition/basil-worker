<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\CallbackEntity;
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

        $this->callback = CallbackEntity::create('type', []);
        $this->callbackStore->store($this->callback);
    }

    /**
     * @dataProvider setQueuedDataProvider
     *
     * @param CallbackEntity::STATE_* $initialState
     * @param CallbackEntity::STATE_* $expectedState
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
            CallbackEntity::STATE_AWAITING => [
                'initialState' => CallbackEntity::STATE_AWAITING,
                'expectedState' => CallbackEntity::STATE_QUEUED,
            ],
            CallbackEntity::STATE_QUEUED => [
                'initialState' => CallbackEntity::STATE_QUEUED,
                'expectedState' => CallbackEntity::STATE_QUEUED,
            ],
            CallbackEntity::STATE_SENDING => [
                'initialState' => CallbackEntity::STATE_SENDING,
                'expectedState' => CallbackEntity::STATE_SENDING,
            ],
            CallbackEntity::STATE_FAILED => [
                'initialState' => CallbackEntity::STATE_FAILED,
                'expectedState' => CallbackEntity::STATE_FAILED,
            ],
            CallbackEntity::STATE_COMPLETE => [
                'initialState' => CallbackEntity::STATE_COMPLETE,
                'expectedState' => CallbackEntity::STATE_COMPLETE,
            ],
        ];
    }

    /**
     * @dataProvider setSendingDataProvider
     *
     * @param CallbackEntity::STATE_* $initialState
     * @param CallbackEntity::STATE_* $expectedState
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
            CallbackEntity::STATE_AWAITING => [
                'initialState' => CallbackEntity::STATE_AWAITING,
                'expectedState' => CallbackEntity::STATE_AWAITING,
            ],
            CallbackEntity::STATE_QUEUED => [
                'initialState' => CallbackEntity::STATE_QUEUED,
                'expectedState' => CallbackEntity::STATE_SENDING,
            ],
            CallbackEntity::STATE_SENDING => [
                'initialState' => CallbackEntity::STATE_SENDING,
                'expectedState' => CallbackEntity::STATE_SENDING,
            ],
            CallbackEntity::STATE_FAILED => [
                'initialState' => CallbackEntity::STATE_FAILED,
                'expectedState' => CallbackEntity::STATE_FAILED,
            ],
            CallbackEntity::STATE_COMPLETE => [
                'initialState' => CallbackEntity::STATE_COMPLETE,
                'expectedState' => CallbackEntity::STATE_COMPLETE,
            ],
        ];
    }

    /**
     * @dataProvider setFailedDataProvider
     *
     * @param CallbackEntity::STATE_* $initialState
     * @param CallbackEntity::STATE_* $expectedState
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
            CallbackEntity::STATE_AWAITING => [
                'initialState' => CallbackEntity::STATE_AWAITING,
                'expectedState' => CallbackEntity::STATE_AWAITING,
            ],
            CallbackEntity::STATE_QUEUED => [
                'initialState' => CallbackEntity::STATE_QUEUED,
                'expectedState' => CallbackEntity::STATE_QUEUED,
            ],
            CallbackEntity::STATE_SENDING => [
                'initialState' => CallbackEntity::STATE_SENDING,
                'expectedState' => CallbackEntity::STATE_FAILED,
            ],
            CallbackEntity::STATE_FAILED => [
                'initialState' => CallbackEntity::STATE_FAILED,
                'expectedState' => CallbackEntity::STATE_FAILED,
            ],
            CallbackEntity::STATE_COMPLETE => [
                'initialState' => CallbackEntity::STATE_COMPLETE,
                'expectedState' => CallbackEntity::STATE_COMPLETE,
            ],
        ];
    }

    /**
     * @dataProvider setCompleteDataProvider
     *
     * @param CallbackEntity::STATE_* $initialState
     * @param CallbackEntity::STATE_* $expectedState
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
            CallbackEntity::STATE_AWAITING => [
                'initialState' => CallbackEntity::STATE_AWAITING,
                'expectedState' => CallbackEntity::STATE_AWAITING,
            ],
            CallbackEntity::STATE_QUEUED => [
                'initialState' => CallbackEntity::STATE_QUEUED,
                'expectedState' => CallbackEntity::STATE_QUEUED,
            ],
            CallbackEntity::STATE_SENDING => [
                'initialState' => CallbackEntity::STATE_SENDING,
                'expectedState' => CallbackEntity::STATE_COMPLETE,
            ],
            CallbackEntity::STATE_FAILED => [
                'initialState' => CallbackEntity::STATE_FAILED,
                'expectedState' => CallbackEntity::STATE_FAILED,
            ],
            CallbackEntity::STATE_COMPLETE => [
                'initialState' => CallbackEntity::STATE_COMPLETE,
                'expectedState' => CallbackEntity::STATE_COMPLETE,
            ],
        ];
    }

    /**
     * @dataProvider setSendingDataProvider
     *
     * @param CallbackEntity::STATE_* $initialState
     * @param CallbackEntity::STATE_* $expectedState
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
