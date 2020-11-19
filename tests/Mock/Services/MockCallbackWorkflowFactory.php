<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services;

use App\Model\Workflow\CallbackWorkflow;
use App\Services\CallbackWorkflowFactory;
use Mockery\MockInterface;

class MockCallbackWorkflowFactory
{
    /**
     * @var CallbackWorkflowFactory|MockInterface
     */
    private CallbackWorkflowFactory $callbackWorkflowFactory;

    public function __construct()
    {
        $this->callbackWorkflowFactory = \Mockery::mock(CallbackWorkflowFactory::class);
    }

    public function getMock(): CallbackWorkflowFactory
    {
        return $this->callbackWorkflowFactory;
    }

    public function withCreateCall(CallbackWorkflow $callbackWorkflow): self
    {
        $this->callbackWorkflowFactory
            ->shouldReceive('create')
            ->andReturn($callbackWorkflow);

        return $this;
    }
}
