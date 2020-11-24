<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Callback\CallbackInterface;
use App\Model\CallbackState;
use App\Services\CallbackStateFactory;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Model\EndToEndJob\InvokableCollection;
use App\Tests\Services\InvokableFactory\CallbackSetup;
use App\Tests\Services\InvokableFactory\CallbackSetupInvokableFactory;
use App\Tests\Services\InvokableHandler;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class CallbackStateFactoryTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private CallbackStateFactory $callbackStateFactory;
    private InvokableHandler $invokableHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    /**
     * @dataProvider createDataProvider
     *
     * @param array<CallbackInterface::STATE_*> $callbackStates
     * @param CallbackState $expectedState
     */
    public function testCreate(array $callbackStates, CallbackState $expectedState)
    {
        $callbackCreationInvocations = [];
        foreach ($callbackStates as $callbackState) {
            $callbackCreationInvocations[] = CallbackSetupInvokableFactory::setup(
                (new CallbackSetup())->withState($callbackState)
            );
        }

        $this->invokableHandler->invoke(new InvokableCollection($callbackCreationInvocations));

        self::assertEquals($expectedState, $this->callbackStateFactory->create());
    }

    public function createDataProvider(): array
    {
        return [
            'no callbacks' => [
                'callbackStates' => [],
                'expectedState' => new CallbackState(CallbackState::STATE_AWAITING),
            ],
            'awaiting, sending, queued' => [
                'callbackStates' => [
                    CallbackInterface::STATE_AWAITING,
                    CallbackInterface::STATE_QUEUED,
                    CallbackInterface::STATE_SENDING,
                ],
                'expectedState' => new CallbackState(CallbackState::STATE_RUNNING),
            ],
            'awaiting, sending, queued, complete' => [
                'callbackStates' => [
                    CallbackInterface::STATE_AWAITING,
                    CallbackInterface::STATE_QUEUED,
                    CallbackInterface::STATE_SENDING,
                    CallbackInterface::STATE_COMPLETE,
                ],
                'expectedState' => new CallbackState(CallbackState::STATE_RUNNING),
            ],
            'awaiting, sending, queued, failed' => [
                'callbackStates' => [
                    CallbackInterface::STATE_AWAITING,
                    CallbackInterface::STATE_QUEUED,
                    CallbackInterface::STATE_SENDING,
                    CallbackInterface::STATE_FAILED,
                ],
                'expectedState' => new CallbackState(CallbackState::STATE_RUNNING),
            ],
            'two complete, three failed' => [
                'callbackStates' => [
                    CallbackInterface::STATE_COMPLETE,
                    CallbackInterface::STATE_COMPLETE,
                    CallbackInterface::STATE_FAILED,
                    CallbackInterface::STATE_FAILED,
                    CallbackInterface::STATE_FAILED,
                ],
                'expectedState' => new CallbackState(CallbackState::STATE_COMPLETE),
            ],
        ];
    }
}
