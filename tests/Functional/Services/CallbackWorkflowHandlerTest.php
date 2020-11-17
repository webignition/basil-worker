<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Callback\CallbackEntity;
use App\Entity\Callback\CallbackInterface;
use App\Services\CallbackStore;
use App\Services\CallbackWorkflowHandler;
use App\Tests\AbstractBaseFunctionalTest;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class CallbackWorkflowHandlerTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private CallbackWorkflowHandler $handler;
    private CallbackStore $callbackStore;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    /**
     * @dataProvider isCompleteDataProvider
     *
     * @param array<CallbackInterface::STATE_*> $callbackStates
     * @param bool $expectedIsComplete
     */
    public function testIsComplete(array $callbackStates, bool $expectedIsComplete)
    {
        foreach ($callbackStates as $callbackState) {
            $callback = CallbackEntity::create(CallbackInterface::TYPE_COMPILE_FAILURE, []);
            $callback->setState($callbackState);
            $this->callbackStore->store($callback);
        }

        self::assertSame($expectedIsComplete, $this->handler->isComplete());
    }

    public function isCompleteDataProvider(): array
    {
        return [
            'no callbacks' => [
                'callbackStates' => [],
                'expectedIsComplete' => false,
            ],
            'three total, none finished' => [
                'callbackStates' => [
                    CallbackInterface::STATE_AWAITING,
                    CallbackInterface::STATE_QUEUED,
                    CallbackInterface::STATE_SENDING,
                ],
                'expectedIsComplete' => false,
            ],
            'four total, one complete' => [
                'callbackStates' => [
                    CallbackInterface::STATE_AWAITING,
                    CallbackInterface::STATE_QUEUED,
                    CallbackInterface::STATE_SENDING,
                    CallbackInterface::STATE_COMPLETE,
                ],
                'expectedIsComplete' => false,
            ],
            'four total, one failed' => [
                'callbackStates' => [
                    CallbackInterface::STATE_AWAITING,
                    CallbackInterface::STATE_QUEUED,
                    CallbackInterface::STATE_SENDING,
                    CallbackInterface::STATE_FAILED,
                ],
                'expectedIsComplete' => false,
            ],
            'five total, two complete, three failed' => [
                'callbackStates' => [
                    CallbackInterface::STATE_COMPLETE,
                    CallbackInterface::STATE_COMPLETE,
                    CallbackInterface::STATE_FAILED,
                    CallbackInterface::STATE_FAILED,
                    CallbackInterface::STATE_FAILED,
                ],
                'expectedIsComplete' => true,
            ],
        ];
    }
}
