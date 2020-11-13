<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\CallbackEntityInterface;
use App\Entity\ExecuteDocumentReceivedCallback;
use App\Services\CallbackStateMutator;
use App\Services\CallbackStore;
use App\Tests\AbstractBaseFunctionalTest;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;
use webignition\YamlDocument\Document;

class CallbackStateMutatorTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private CallbackStateMutator $mutator;
    private CallbackEntityInterface $callback;
    private CallbackStore $callbackStore;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();

        $this->callback = ExecuteDocumentReceivedCallback::create(new Document('[]'));
        $this->callbackStore->store($this->callback);
    }

    /**
     * @dataProvider setQueuedDataProvider
     *
     * @param CallbackEntityInterface::STATE_* $initialState
     * @param CallbackEntityInterface::STATE_* $expectedState
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
            CallbackEntityInterface::STATE_AWAITING => [
                'initialState' => CallbackEntityInterface::STATE_AWAITING,
                'expectedState' => CallbackEntityInterface::STATE_QUEUED,
            ],
            CallbackEntityInterface::STATE_QUEUED => [
                'initialState' => CallbackEntityInterface::STATE_QUEUED,
                'expectedState' => CallbackEntityInterface::STATE_QUEUED,
            ],
            CallbackEntityInterface::STATE_SENDING => [
                'initialState' => CallbackEntityInterface::STATE_SENDING,
                'expectedState' => CallbackEntityInterface::STATE_SENDING,
            ],
            CallbackEntityInterface::STATE_FAILED => [
                'initialState' => CallbackEntityInterface::STATE_FAILED,
                'expectedState' => CallbackEntityInterface::STATE_FAILED,
            ],
            CallbackEntityInterface::STATE_COMPLETE => [
                'initialState' => CallbackEntityInterface::STATE_COMPLETE,
                'expectedState' => CallbackEntityInterface::STATE_COMPLETE,
            ],
        ];
    }

    /**
     * @dataProvider setSendingDataProvider
     *
     * @param CallbackEntityInterface::STATE_* $initialState
     * @param CallbackEntityInterface::STATE_* $expectedState
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
            CallbackEntityInterface::STATE_AWAITING => [
                'initialState' => CallbackEntityInterface::STATE_AWAITING,
                'expectedState' => CallbackEntityInterface::STATE_AWAITING,
            ],
            CallbackEntityInterface::STATE_QUEUED => [
                'initialState' => CallbackEntityInterface::STATE_QUEUED,
                'expectedState' => CallbackEntityInterface::STATE_SENDING,
            ],
            CallbackEntityInterface::STATE_SENDING => [
                'initialState' => CallbackEntityInterface::STATE_SENDING,
                'expectedState' => CallbackEntityInterface::STATE_SENDING,
            ],
            CallbackEntityInterface::STATE_FAILED => [
                'initialState' => CallbackEntityInterface::STATE_FAILED,
                'expectedState' => CallbackEntityInterface::STATE_FAILED,
            ],
            CallbackEntityInterface::STATE_COMPLETE => [
                'initialState' => CallbackEntityInterface::STATE_COMPLETE,
                'expectedState' => CallbackEntityInterface::STATE_COMPLETE,
            ],
        ];
    }

    /**
     * @dataProvider setFailedDataProvider
     *
     * @param CallbackEntityInterface::STATE_* $initialState
     * @param CallbackEntityInterface::STATE_* $expectedState
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
            CallbackEntityInterface::STATE_AWAITING => [
                'initialState' => CallbackEntityInterface::STATE_AWAITING,
                'expectedState' => CallbackEntityInterface::STATE_AWAITING,
            ],
            CallbackEntityInterface::STATE_QUEUED => [
                'initialState' => CallbackEntityInterface::STATE_QUEUED,
                'expectedState' => CallbackEntityInterface::STATE_QUEUED,
            ],
            CallbackEntityInterface::STATE_SENDING => [
                'initialState' => CallbackEntityInterface::STATE_SENDING,
                'expectedState' => CallbackEntityInterface::STATE_FAILED,
            ],
            CallbackEntityInterface::STATE_FAILED => [
                'initialState' => CallbackEntityInterface::STATE_FAILED,
                'expectedState' => CallbackEntityInterface::STATE_FAILED,
            ],
            CallbackEntityInterface::STATE_COMPLETE => [
                'initialState' => CallbackEntityInterface::STATE_COMPLETE,
                'expectedState' => CallbackEntityInterface::STATE_COMPLETE,
            ],
        ];
    }

    /**
     * @dataProvider setCompleteDataProvider
     *
     * @param CallbackEntityInterface::STATE_* $initialState
     * @param CallbackEntityInterface::STATE_* $expectedState
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
            CallbackEntityInterface::STATE_AWAITING => [
                'initialState' => CallbackEntityInterface::STATE_AWAITING,
                'expectedState' => CallbackEntityInterface::STATE_AWAITING,
            ],
            CallbackEntityInterface::STATE_QUEUED => [
                'initialState' => CallbackEntityInterface::STATE_QUEUED,
                'expectedState' => CallbackEntityInterface::STATE_QUEUED,
            ],
            CallbackEntityInterface::STATE_SENDING => [
                'initialState' => CallbackEntityInterface::STATE_SENDING,
                'expectedState' => CallbackEntityInterface::STATE_COMPLETE,
            ],
            CallbackEntityInterface::STATE_FAILED => [
                'initialState' => CallbackEntityInterface::STATE_FAILED,
                'expectedState' => CallbackEntityInterface::STATE_FAILED,
            ],
            CallbackEntityInterface::STATE_COMPLETE => [
                'initialState' => CallbackEntityInterface::STATE_COMPLETE,
                'expectedState' => CallbackEntityInterface::STATE_COMPLETE,
            ],
        ];
    }

    /**
     * @dataProvider setSendingDataProvider
     *
     * @param CallbackEntityInterface::STATE_* $initialState
     * @param CallbackEntityInterface::STATE_* $expectedState
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
