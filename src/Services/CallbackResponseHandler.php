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
    private BackoffStrategyFactory $backoffStrategyFactory;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        BackoffStrategyFactory $backoffStrategyFactory
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->backoffStrategyFactory = $backoffStrategyFactory;
    }

    /**
     * @param CallbackInterface $callback
     * @param ClientExceptionInterface|ResponseInterface $context
     */
    public function handle(CallbackInterface $callback, object $context): void
    {
        $callback->incrementRetryCount();
        $callback = $this->createNextCallback($callback, $context);

        $this->eventDispatcher->dispatch(new CallbackHttpErrorEvent($callback, $context));
    }

    /**
     * @param CallbackInterface $callback
     * @param ClientExceptionInterface|ResponseInterface $context
     * @return CallbackInterface
     */
    private function createNextCallback(CallbackInterface $callback, object $context): CallbackInterface
    {
        if (0 === $callback->getRetryCount()) {
            return $callback;
        }

        return new DelayedCallback(
            $callback,
            $this->backoffStrategyFactory->create($context)
        );
    }
}
