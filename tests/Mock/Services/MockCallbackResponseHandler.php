<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services;

use App\Entity\Callback\CallbackInterface;
use App\Services\CallbackResponseHandler;
use Mockery\MockInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;

class MockCallbackResponseHandler
{
    /**
     * @var CallbackResponseHandler|MockInterface
     */
    private CallbackResponseHandler $callbackResponseHandler;

    public function __construct()
    {
        $this->callbackResponseHandler = \Mockery::mock(CallbackResponseHandler::class);
    }

    public function getMock(): CallbackResponseHandler
    {
        return $this->callbackResponseHandler;
    }

    public function withHandleResponseCall(CallbackInterface $callback, ResponseInterface $response): self
    {
        $this->callbackResponseHandler
            ->shouldReceive('handleResponse')
            ->once()
            ->with($callback, $response);

        return $this;
    }

    public function withoutHandleResponseCall(): self
    {
        $this->callbackResponseHandler
            ->shouldNotReceive('handleResponse');

        return $this;
    }

    public function withHandleClientExceptionCall(
        CallbackInterface $callback,
        ClientExceptionInterface $exception
    ): self {
        $this->callbackResponseHandler
            ->shouldReceive('handleClientException')
            ->once()
            ->with($callback, $exception);

        return $this;
    }

    public function withoutHandleClientExceptionCall(): self
    {
        $this->callbackResponseHandler
            ->shouldNotReceive('handleClientException');

        return $this;
    }
}
