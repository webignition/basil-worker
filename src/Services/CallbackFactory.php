<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Callback\CallbackInterface;
use App\Services\EventCallbackFactory\EventCallbackFactoryInterface;
use Symfony\Contracts\EventDispatcher\Event;

class CallbackFactory
{
    /**
     * @var EventCallbackFactoryInterface[]
     */
    private array $eventCallbackFactories;

    /**
     * @param array<mixed> $eventCallbackFactories
     */
    public function __construct(
        array $eventCallbackFactories
    ) {
        $this->eventCallbackFactories = array_filter($eventCallbackFactories, function ($item) {
            return $item instanceof EventCallbackFactoryInterface;
        });
    }

    public function createForEvent(Event $event): ?CallbackInterface
    {
        foreach ($this->eventCallbackFactories as $eventCallbackFactory) {
            if ($eventCallbackFactory->handles($event)) {
                return $eventCallbackFactory->createForEvent($event);
            }
        }

        return null;
    }
}
