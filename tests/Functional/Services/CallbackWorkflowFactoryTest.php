<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Callback\CallbackEntity;
use App\Entity\Callback\CallbackInterface;
use App\Model\Workflow\CallbackWorkflow;
use App\Services\CallbackStore;
use App\Services\CallbackWorkflowFactory;
use App\Tests\AbstractBaseFunctionalTest;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class CallbackWorkflowFactoryTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private CallbackWorkflowFactory $callbackWorkflowFactory;
    private CallbackStore $callbackStore;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    /**
     * @dataProvider createDataProvider
     *
     * @param array<CallbackInterface::STATE_*> $callbackStates
     * @param CallbackWorkflow $expectedWorkflow
     */
    public function testCreate(array $callbackStates, CallbackWorkflow $expectedWorkflow)
    {
        foreach ($callbackStates as $callbackState) {
            $callback = CallbackEntity::create(CallbackInterface::TYPE_COMPILE_FAILURE, []);
            $callback->setState($callbackState);
            $this->callbackStore->store($callback);
        }

        self::assertEquals($expectedWorkflow, $this->callbackWorkflowFactory->create());
    }

    public function createDataProvider(): array
    {
        return [
            'no callbacks' => [
                'callbackStates' => [],
                'expectedWorkflow' => new CallbackWorkflow(0, 0),
            ],
            'three total, none finished' => [
                'callbackStates' => [
                    CallbackInterface::STATE_AWAITING,
                    CallbackInterface::STATE_QUEUED,
                    CallbackInterface::STATE_SENDING,
                ],
                'expectedWorkflow' => new CallbackWorkflow(3, 0),
            ],
            'four total, one complete' => [
                'callbackStates' => [
                    CallbackInterface::STATE_AWAITING,
                    CallbackInterface::STATE_QUEUED,
                    CallbackInterface::STATE_SENDING,
                    CallbackInterface::STATE_COMPLETE,
                ],
                'expectedWorkflow' => new CallbackWorkflow(4, 1),
            ],
            'four total, one failed' => [
                'callbackStates' => [
                    CallbackInterface::STATE_AWAITING,
                    CallbackInterface::STATE_QUEUED,
                    CallbackInterface::STATE_SENDING,
                    CallbackInterface::STATE_FAILED,
                ],
                'expectedWorkflow' => new CallbackWorkflow(4, 1),
            ],
            'eight total, two complete, three failed' => [
                'callbackStates' => [
                    CallbackInterface::STATE_AWAITING,
                    CallbackInterface::STATE_QUEUED,
                    CallbackInterface::STATE_SENDING,
                    CallbackInterface::STATE_COMPLETE,
                    CallbackInterface::STATE_COMPLETE,
                    CallbackInterface::STATE_FAILED,
                    CallbackInterface::STATE_FAILED,
                    CallbackInterface::STATE_FAILED,
                ],
                'expectedWorkflow' => new CallbackWorkflow(8, 5),
            ],
        ];
    }
}
