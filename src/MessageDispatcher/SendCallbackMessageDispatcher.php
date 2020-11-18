<?php

declare(strict_types=1);

namespace App\MessageDispatcher;

use App\Entity\Callback\CallbackInterface;
use App\Entity\Callback\StampedCallbackInterface;
use App\Event\Callback\CallbackHttpExceptionEvent;
use App\Event\Callback\CallbackHttpResponseEvent;
use App\Event\CallbackEventInterface;
use App\Event\SourceCompile\SourceCompileFailureEvent;
use App\Event\TestExecuteDocumentReceivedEvent;
use App\Message\SendCallback;
use App\Services\CallbackStateMutator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class SendCallbackMessageDispatcher implements EventSubscriberInterface
{
    private MessageBusInterface $messageBus;
    private CallbackStateMutator $callbackStateMutator;

    public function __construct(MessageBusInterface $messageBus, CallbackStateMutator $callbackStateMutator)
    {
        $this->messageBus = $messageBus;
        $this->callbackStateMutator = $callbackStateMutator;
    }

    public static function getSubscribedEvents()
    {
        return [
            CallbackHttpExceptionEvent::class => [
                ['dispatchForCallbackEvent', 0],
            ],
            CallbackHttpResponseEvent::class => [
                ['dispatchForCallbackEvent', 0],
            ],
            SourceCompileFailureEvent::class => [
                ['dispatchForCallbackEvent', 0],
            ],
            TestExecuteDocumentReceivedEvent::class => [
                ['dispatchForCallbackEvent', 0],
            ],
        ];
    }

    public function dispatchForCallbackEvent(CallbackEventInterface $event): void
    {
        $callback = $event->getCallback();

        $this->callbackStateMutator->setQueued($callback);
        $this->messageBus->dispatch($this->createCallbackEnvelope($callback));
    }

    /**
     * @param CallbackInterface $callback
     *
     * @return Envelope
     */
    private function createCallbackEnvelope(CallbackInterface $callback): Envelope
    {
        $sendCallbackMessage = new SendCallback($callback);
        $stamps = [];

        if ($callback instanceof StampedCallbackInterface) {
            $stampCollection = $callback->getStamps();
            if ($stampCollection->hasStamps()) {
                $stamps = $stampCollection->getStamps();
            }
        }

        return new Envelope($sendCallbackMessage, $stamps);
    }
}
