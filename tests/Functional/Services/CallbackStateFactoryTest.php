<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Callback\CallbackInterface;
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
     * @dataProvider isDataProvider
     *
     * @param array<CallbackInterface::STATE_*> $callbackStates
     * @param array<CallbackStateFactory::STATE_*> $expectedIsStates
     * @param array<CallbackStateFactory::STATE_*> $expectedIsNotStates
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
                    CallbackStateFactory::STATE_AWAITING,
                ],
                'expectedIsNotStates' => [
                    CallbackStateFactory::STATE_RUNNING,
                    CallbackStateFactory::STATE_COMPLETE,
                ],
            ],
            'awaiting, sending, queued' => [
                'callbackStates' => [
                    CallbackInterface::STATE_AWAITING,
                    CallbackInterface::STATE_QUEUED,
                    CallbackInterface::STATE_SENDING,
                ],
                'expectedIsStates' => [
                    CallbackStateFactory::STATE_RUNNING,
                ],
                'expectedIsNotStates' => [
                    CallbackStateFactory::STATE_AWAITING,
                    CallbackStateFactory::STATE_COMPLETE,
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
                    CallbackStateFactory::STATE_RUNNING,
                ],
                'expectedIsNotStates' => [
                    CallbackStateFactory::STATE_AWAITING,
                    CallbackStateFactory::STATE_COMPLETE,
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
                    CallbackStateFactory::STATE_RUNNING,
                ],
                'expectedIsNotStates' => [
                    CallbackStateFactory::STATE_AWAITING,
                    CallbackStateFactory::STATE_COMPLETE,
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
                    CallbackStateFactory::STATE_COMPLETE,
                ],
                'expectedIsNotStates' => [
                    CallbackStateFactory::STATE_AWAITING,
                    CallbackStateFactory::STATE_RUNNING,
                ],
            ],
        ];
    }
}
