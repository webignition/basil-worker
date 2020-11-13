<?php

declare(strict_types=1);

namespace App\Services;

use App\Event\Callback\CallbackHttpExceptionEvent;
use App\Event\Callback\CallbackHttpResponseEvent;
use App\Model\Callback\CallbackInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Contracts\EventDispatcher\Event;

class CallbackResponseHandler
{
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function handleResponse(CallbackInterface $callback, ResponseInterface $response): void
    {
        $this->incrementRetryCountAndDispatch($callback, new CallbackHttpResponseEvent($callback, $response));
    }

    public function handleClientException(CallbackInterface $callback, ClientExceptionInterface $clientException): void
    {
        $this->incrementRetryCountAndDispatch($callback, new CallbackHttpExceptionEvent($callback, $clientException));
    }

    private function incrementRetryCountAndDispatch(CallbackInterface $callback, Event $event): void
    {
        $callback->incrementRetryCount();
        $this->eventDispatcher->dispatch($event);
    }
}
