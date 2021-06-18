<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\SendCallbackMessage;
use App\Repository\CallbackRepository;
use App\Services\CallbackSender;
use App\Services\CallbackStateMutator;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;

class SendCallbackHandler implements MessageHandlerInterface
{
    public function __construct(
        private CallbackRepository $repository,
        private CallbackSender $sender,
        private CallbackStateMutator $stateMutator
    ) {
    }

    public function __invoke(SendCallbackMessage $message): void
    {
        $callback = $this->repository->find($message->getCallbackId());

        if ($callback instanceof CallbackInterface) {
            $this->stateMutator->setSending($callback);
            $this->sender->send($callback);
        }
    }
}
