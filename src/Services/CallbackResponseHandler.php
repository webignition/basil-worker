<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Callback\CallbackInterface;
use App\Entity\Callback\DelayedCallback;
use App\Event\Callback\CallbackHttpErrorEvent;
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
        $callback = $this->createNextCallback($callback);

        $this->eventDispatcher->dispatch(new CallbackHttpErrorEvent($callback, $response));
    }

    public function handleClientException(CallbackInterface $callback, ClientExceptionInterface $clientException): void
    {
        $callback->incrementRetryCount();
        $callback = $this->createNextCallback($callback);

        $this->eventDispatcher->dispatch(new CallbackHttpErrorEvent($callback, $clientException));
    }

    private function createNextCallback(CallbackInterface $callback): CallbackInterface
    {
        if (0 === $callback->getRetryCount()) {
            return $callback;
        }

        return DelayedCallback::create($callback);
    }
}
