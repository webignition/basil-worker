<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Callback\CallbackInterface;
use App\Entity\Callback\DelayedCallback;
use App\Event\CallbackHttpErrorEvent;
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

    /**
     * @param CallbackInterface $callback
     * @param ClientExceptionInterface|ResponseInterface $context
     */
    public function handle(CallbackInterface $callback, object $context): void
    {
        $callback->incrementRetryCount();
        if (0 !== $callback->getRetryCount()) {
            $callback = DelayedCallback::create($callback);
        }

        $this->eventDispatcher->dispatch(new CallbackHttpErrorEvent($callback, $context));
    }
}
