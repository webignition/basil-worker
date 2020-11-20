<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Callback\CallbackInterface;
use App\Model\Workflow\CallbackWorkflow;
use App\Services\CallbackWorkflowFactory;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Model\EndToEndJob\InvokableCollection;
use App\Tests\Services\InvokableFactory\CallbackSetup;
use App\Tests\Services\InvokableFactory\CallbackSetupInvokableFactory;
use App\Tests\Services\InvokableHandler;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class CallbackWorkflowFactoryTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private CallbackWorkflowFactory $callbackWorkflowFactory;
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
     * @param CallbackWorkflow $expectedWorkflow
     */
    public function testCreate(array $callbackStates, CallbackWorkflow $expectedWorkflow)
    {
        $callbackCreationInvocations = [];
        foreach ($callbackStates as $callbackState) {
            $callbackCreationInvocations[] = CallbackSetupInvokableFactory::setup(
                (new CallbackSetup())->withState($callbackState)
            );
        }

        $this->invokableHandler->invoke(new InvokableCollection($callbackCreationInvocations));

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
