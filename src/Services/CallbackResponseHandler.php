<?php

declare(strict_types=1);

namespace App\Services;

use App\Event\Callback\CallbackHttpExceptionEvent;
use App\Event\Callback\CallbackHttpResponseEvent;
use App\Model\Callback\CallbackInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;

class CallbackResponseHandler
{
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function handleResponse(CallbackInterface $callback, ResponseInterface $response): void
    {
        $callback->incrementRetryCount();
        $this->eventDispatcher->dispatch(new CallbackHttpResponseEvent($callback, $response));
    }

    public function handleClientException(CallbackInterface $callback, ClientExceptionInterface $clientException): void
    {
        $callback->incrementRetryCount();
        $this->eventDispatcher->dispatch(new CallbackHttpExceptionEvent($callback, $clientException));
    }
}
