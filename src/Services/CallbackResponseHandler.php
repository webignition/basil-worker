<?php

declare(strict_types=1);

namespace App\Services;

use App\Event\CallbackHttpExceptionEvent;
use App\Event\CallbackHttpResponseEvent;
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
        if (200 !== $response->getStatusCode()) {
            $this->eventDispatcher->dispatch(
                new CallbackHttpResponseEvent($callback, $response),
                CallbackHttpResponseEvent::NAME
            );
        }
    }

    public function handleClientException(CallbackInterface $callback, ClientExceptionInterface $clientException): void
    {
        $this->eventDispatcher->dispatch(
            new CallbackHttpExceptionEvent($callback, $clientException),
            CallbackHttpExceptionEvent::NAME
        );
    }
}
