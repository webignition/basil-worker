<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services;

use App\Entity\Callback\CallbackInterface;
use App\Services\CallbackResponseHandler;
use Mockery\MockInterface;

class MockCallbackResponseHandler
{
    private CallbackResponseHandler $mock;

    public function __construct()
    {
        $this->mock = \Mockery::mock(CallbackResponseHandler::class);
    }

    public function getMock(): CallbackResponseHandler
    {
        return $this->mock;
    }

    public function withHandleCall(CallbackInterface $callback, object $context): self
    {
        if (false === $this->mock instanceof MockInterface) {
            return $this;
        }

        $this->mock
            ->shouldReceive('handle')
            ->once()
            ->with($callback, $context)
        ;

        return $this;
    }

    public function withoutHandleCall(): self
    {
        if (false === $this->mock instanceof MockInterface) {
            return $this;
        }

        $this->mock
            ->shouldNotReceive('handle')
        ;

        return $this;
    }
}
