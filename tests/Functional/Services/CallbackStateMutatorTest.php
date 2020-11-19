<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Callback\CallbackEntity;
use App\Entity\Callback\CallbackInterface;
use App\Entity\Callback\CompileFailureCallback;
use App\Entity\Callback\DelayedCallback;
use App\Entity\Callback\ExecuteDocumentReceivedCallback;
use App\Model\BackoffStrategy\ExponentialBackoffStrategy;
use App\Services\CallbackStateMutator;
use App\Services\CallbackStore;
use App\Tests\AbstractBaseFunctionalTest;
use webignition\BasilCompilerModels\ErrorOutputInterface;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;
use webignition\YamlDocument\Document;

class CallbackStateMutatorTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private CallbackStateMutator $mutator;
    private CallbackStore $callbackStore;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    /**
     * @dataProvider setQueuedDataProvider
     *
     * @param CallbackInterface::STATE_* $initialState
     * @param CallbackInterface::STATE_* $expectedState
     */
    public function testSetQueued(string $initialState, string $expectedState)
    {
        foreach ($this->createCallbacks() as $callback) {
            $this->doSetAsStateTest(
                $callback,
                $initialState,
                $expectedState,
                function (CallbackInterface $callback) {
                    $this->mutator->setQueued($callback);
                }
            );
        }
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
                'expectedState' => CallbackInterface::STATE_QUEUED,
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
        foreach ($this->createCallbacks() as $callback) {
            $this->doSetAsStateTest(
                $callback,
                $initialState,
                $expectedState,
                function (CallbackInterface $callback) {
                    $this->mutator->setSending($callback);
                }
            );
        }
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
        foreach ($this->createCallbacks() as $callback) {
            $this->doSetAsStateTest(
                $callback,
                $initialState,
                $expectedState,
                function (CallbackInterface $callback) {
                    $this->mutator->setFailed($callback);
                }
            );
        }
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
        foreach ($this->createCallbacks() as $callback) {
            $this->doSetAsStateTest(
                $callback,
                $initialState,
                $expectedState,
                function (CallbackInterface $callback) {
                    $this->mutator->setComplete($callback);
                }
            );
        }
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
     * @param CallbackInterface $callback
     * @param CallbackInterface::STATE_* $initialState
     * @param CallbackInterface::STATE_* $expectedState
     * @param callable $setter
     */
    private function doSetAsStateTest(
        CallbackInterface $callback,
        string $initialState,
        string $expectedState,
        callable $setter
    ): void {
        $callback->setState($initialState);
        $this->callbackStore->store($callback);
        self::assertSame($initialState, $callback->getState());

        $setter($callback);

        self::assertSame($expectedState, $callback->getState());
    }

    /**
     * @return CallbackInterface[]
     */
    private function createCallbacks(): array
    {
        return [
            'default entity' => $this->createCallbackEntity(),
            'compile failure' => $this->createCompileFailureCallback(),
            'execute document received' => $this->createExecuteDocumentReceivedCallback(),
            'delayed default entity' => new DelayedCallback(
                $this->createCallbackEntity(),
                new ExponentialBackoffStrategy()
            ),
            'delayed compile failure' => new DelayedCallback(
                $this->createCompileFailureCallback(),
                new ExponentialBackoffStrategy()
            ),
            'delayed execute document received' => new DelayedCallback(
                $this->createExecuteDocumentReceivedCallback(),
                new ExponentialBackoffStrategy()
            ),
        ];
    }

    private function createCallbackEntity(): CallbackEntity
    {
        return CallbackEntity::create(CallbackInterface::TYPE_COMPILE_FAILURE, []);
    }

    private function createCompileFailureCallback(): CompileFailureCallback
    {
        $errorOutput = \Mockery::mock(ErrorOutputInterface::class);
        $errorOutput
            ->shouldReceive('getData')
            ->andReturn([]);

        return new CompileFailureCallback($errorOutput);
    }

    private function createExecuteDocumentReceivedCallback(): ExecuteDocumentReceivedCallback
    {
        return new ExecuteDocumentReceivedCallback(new Document());
    }
}
