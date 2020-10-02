<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\SendCallback;
use App\Services\CallbackSender;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class SendCallbackHandler implements MessageHandlerInterface
{
    private CallbackSender $callbackSender;

    public function __construct(CallbackSender $callbackSender)
    {
        $this->callbackSender = $callbackSender;
    }

    public function __invoke(SendCallback $message): void
    {
        $this->callbackSender->send($message->getCallback());
    }
}
