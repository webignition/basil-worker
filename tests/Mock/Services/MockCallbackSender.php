<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services;

use App\Services\CallbackSender;
use Mockery\MockInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;

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

    public function withoutSendCall(): self
    {
        $this->callbackSender
            ->shouldNotReceive('send');

        return $this;
    }
}
