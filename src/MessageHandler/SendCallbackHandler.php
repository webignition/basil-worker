<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Callback\CallbackInterface;
use App\Message\SendCallback;
use App\Repository\CallbackRepository;
use App\Services\CallbackSender;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class SendCallbackHandler implements MessageHandlerInterface
{
    private CallbackSender $callbackSender;
    private CallbackRepository $callbackRepository;

    public function __construct(CallbackSender $callbackSender, CallbackRepository $callbackRepository)
    {
        $this->callbackSender = $callbackSender;
        $this->callbackRepository = $callbackRepository;
    }

    public function __invoke(SendCallback $message): void
    {
        $callback = $this->callbackRepository->find($message->getCallbackId());

        if ($callback instanceof CallbackInterface) {
            $this->callbackSender->send($callback);
        }
    }
}
