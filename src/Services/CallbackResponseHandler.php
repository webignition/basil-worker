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
    private int $retryLimit;

    public function __construct(EventDispatcherInterface $eventDispatcher, int $retryLimit)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->retryLimit = $retryLimit;
    }

    public function handleResponse(CallbackInterface $callback, ResponseInterface $response): void
    {
        $statusCode = $response->getStatusCode();

        if ($statusCode >= 300) {
            if (false === $callback->hasReachedRetryLimit($this->retryLimit)) {
                $callback->incrementRetryCount();

                $this->eventDispatcher->dispatch(
                    new CallbackHttpResponseEvent($callback, $response),
                    CallbackHttpResponseEvent::NAME
                );
            }
        }
    }

    public function handleClientException(CallbackInterface $callback, ClientExceptionInterface $clientException): void
    {
        if (false === $callback->hasReachedRetryLimit($this->retryLimit)) {
            $callback->incrementRetryCount();

            $this->eventDispatcher->dispatch(
                new CallbackHttpExceptionEvent($callback, $clientException),
                CallbackHttpExceptionEvent::NAME
            );
        }
    }
}
