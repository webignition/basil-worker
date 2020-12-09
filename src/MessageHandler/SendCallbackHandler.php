<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\SendCallback;
use App\Services\CallbackSender;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;
use webignition\BasilWorker\PersistenceBundle\Services\CallbackStateMutator;
use webignition\BasilWorker\PersistenceBundle\Services\Store\CallbackStore;

class SendCallbackHandler implements MessageHandlerInterface
{
    private CallbackSender $sender;
    private CallbackStore $callbackStore;
    private CallbackStateMutator $stateMutator;

    public function __construct(
        CallbackSender $sender,
        CallbackStore $callbackStore,
        CallbackStateMutator $stateMutator
    ) {
        $this->sender = $sender;
        $this->callbackStore = $callbackStore;
        $this->stateMutator = $stateMutator;
    }

    public function __invoke(SendCallback $message): void
    {
        $callback = $this->callbackStore->get($message->getCallbackId());

        if ($callback instanceof CallbackInterface) {
            $this->stateMutator->setSending($callback);
            $this->sender->send($callback);
        }
    }
}
