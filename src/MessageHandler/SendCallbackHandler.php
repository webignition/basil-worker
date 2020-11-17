<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Callback\CallbackInterface;
use App\Message\SendCallback;
use App\Repository\CallbackRepository;
use App\Services\CallbackSender;
use App\Services\CallbackStateMutator;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class SendCallbackHandler implements MessageHandlerInterface
{
    private CallbackSender $sender;
    private CallbackRepository $repository;
    private CallbackStateMutator $stateMutator;

    public function __construct(
        CallbackSender $sender,
        CallbackRepository $repository,
        CallbackStateMutator $stateMutator
    ) {
        $this->sender = $sender;
        $this->repository = $repository;
        $this->stateMutator = $stateMutator;
    }

    public function __invoke(SendCallback $message): void
    {
        $callback = $this->repository->find($message->getCallbackId());

        if ($callback instanceof CallbackInterface) {
            $this->stateMutator->setSending($callback);
            $this->sender->send($callback);
        }
    }
}
