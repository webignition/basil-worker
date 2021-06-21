<?php

declare(strict_types=1);

namespace App\Services\EventCallbackFactory;

use App\Event\SourceCompilation\StartedEvent;
use Symfony\Contracts\EventDispatcher\Event;
use App\Entity\Callback\CallbackInterface;

class CompilationStartedEventCallbackFactory extends AbstractCompilationEventCallbackFactory
{
    public function handles(Event $event): bool
    {
        return $event instanceof StartedEvent;
    }

    public function createForEvent(Event $event): ?CallbackInterface
    {
        if ($event instanceof StartedEvent) {
            return $this->create(
                CallbackInterface::TYPE_COMPILATION_STARTED,
                $this->createPayload($event)
            );
        }

        return null;
    }
}
