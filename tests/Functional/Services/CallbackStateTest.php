<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Services\CallbackState;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Model\EndToEndJob\InvokableCollection;
use App\Tests\Services\InvokableFactory\CallbackSetup;
use App\Tests\Services\InvokableFactory\CallbackSetupInvokableFactory;
use App\Tests\Services\InvokableHandler;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class CallbackStateTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private CallbackState $callbackStateFactory;
    private InvokableHandler $invokableHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    /**
     * @dataProvider isDataProvider
     *
     * @param array<CallbackInterface::STATE_*> $callbackStates
     * @param array<CallbackState::STATE_*> $expectedIsStates
     * @param array<CallbackState::STATE_*> $expectedIsNotStates
     */
    public function testIs(array $callbackStates, array $expectedIsStates, array $expectedIsNotStates)
    {
        $callbackCreationInvocations = [];
        foreach ($callbackStates as $callbackState) {
            $callbackCreationInvocations[] = CallbackSetupInvokableFactory::setup(
                (new CallbackSetup())->withState($callbackState)
            );
        }

        $this->invokableHandler->invoke(new InvokableCollection($callbackCreationInvocations));

        self::assertTrue($this->callbackStateFactory->is(...$expectedIsStates));
        self::assertFalse($this->callbackStateFactory->is(...$expectedIsNotStates));
    }

    public function isDataProvider(): array
    {
        return [
            'no callbacks' => [
                'callbackStates' => [],
                'expectedIsStates' => [
                    CallbackState::STATE_AWAITING,
                ],
                'expectedIsNotStates' => [
                    CallbackState::STATE_RUNNING,
                    CallbackState::STATE_COMPLETE,
                ],
            ],
            'awaiting, sending, queued' => [
                'callbackStates' => [
                    CallbackInterface::STATE_AWAITING,
                    CallbackInterface::STATE_QUEUED,
                    CallbackInterface::STATE_SENDING,
                ],
                'expectedIsStates' => [
                    CallbackState::STATE_RUNNING,
                ],
                'expectedIsNotStates' => [
                    CallbackState::STATE_AWAITING,
                    CallbackState::STATE_COMPLETE,
                ],
            ],
            'awaiting, sending, queued, complete' => [
                'callbackStates' => [
                    CallbackInterface::STATE_AWAITING,
                    CallbackInterface::STATE_QUEUED,
                    CallbackInterface::STATE_SENDING,
                    CallbackInterface::STATE_COMPLETE,
                ],
                'expectedIsStates' => [
                    CallbackState::STATE_RUNNING,
                ],
                'expectedIsNotStates' => [
                    CallbackState::STATE_AWAITING,
                    CallbackState::STATE_COMPLETE,
                ],
            ],
            'awaiting, sending, queued, failed' => [
                'callbackStates' => [
                    CallbackInterface::STATE_AWAITING,
                    CallbackInterface::STATE_QUEUED,
                    CallbackInterface::STATE_SENDING,
                    CallbackInterface::STATE_FAILED,
                ],
                'expectedIsStates' => [
                    CallbackState::STATE_RUNNING,
                ],
                'expectedIsNotStates' => [
                    CallbackState::STATE_AWAITING,
                    CallbackState::STATE_COMPLETE,
                ],
            ],
            'two complete, three failed' => [
                'callbackStates' => [
                    CallbackInterface::STATE_COMPLETE,
                    CallbackInterface::STATE_COMPLETE,
                    CallbackInterface::STATE_FAILED,
                    CallbackInterface::STATE_FAILED,
                    CallbackInterface::STATE_FAILED,
                ],
                'expectedIsStates' => [
                    CallbackState::STATE_COMPLETE,
                ],
                'expectedIsNotStates' => [
                    CallbackState::STATE_AWAITING,
                    CallbackState::STATE_RUNNING,
                ],
            ],
        ];
    }
}
