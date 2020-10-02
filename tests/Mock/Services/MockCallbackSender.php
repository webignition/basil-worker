<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services;

use App\Model\Callback\CallbackInterface;
use App\Services\CallbackSender;
use Mockery\MockInterface;

class MockCallbackSender
{
    /**
     * @var CallbackSender|MockInterface
     */
    private CallbackSender $callbackSender;

    public function __construct()
    {
        $this->callbackSender = \Mockery::mock(CallbackSender::class);
    }

    public function getMock(): CallbackSender
    {
        return $this->callbackSender;
    }

    public function withSendCall(CallbackInterface $callback): self
    {
        $this->callbackSender
            ->shouldReceive('send')
            ->with($callback);

        return $this;
    }
}
