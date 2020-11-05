<?php

declare(strict_types=1);

namespace App\Services;

use App\Event\Callback\CallbackHttpExceptionEvent;
use App\Event\Callback\CallbackHttpResponseEvent;
use App\Model\Callback\CallbackInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CallbackResponseHandler
{
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function handleResponse(CallbackInterface $callback, ResponseInterface $response): void
    {
        $statusCode = $response->getStatusCode();

        if ($statusCode >= 300) {
            $callback->incrementRetryCount();

            $this->eventDispatcher->dispatch(new CallbackHttpResponseEvent($callback, $response));
        }
    }

    public function handleClientException(CallbackInterface $callback, ClientExceptionInterface $clientException): void
    {
        $callback->incrementRetryCount();

        $this->eventDispatcher->dispatch(new CallbackHttpExceptionEvent($callback, $clientException));
    }
}
