<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Callback\CallbackInterface;
use Doctrine\ORM\EntityManagerInterface;

class CallbackStateMutator
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function setQueued(CallbackInterface $callback): void
    {
        if (in_array($callback->getState(), [CallbackInterface::STATE_AWAITING, CallbackInterface::STATE_SENDING])) {
            $this->set($callback, CallbackInterface::STATE_QUEUED);
        }
    }

    public function setSending(CallbackInterface $callback): void
    {
        if (CallbackInterface::STATE_QUEUED === $callback->getState()) {
            $this->set($callback, CallbackInterface::STATE_SENDING);
        }
    }

    public function setFailed(CallbackInterface $callback): void
    {
        if (in_array($callback->getState(), [CallbackInterface::STATE_QUEUED, CallbackInterface::STATE_SENDING])) {
            $this->set($callback, CallbackInterface::STATE_FAILED);
        }
    }

    public function setComplete(CallbackInterface $callback): void
    {
        if (CallbackInterface::STATE_SENDING === $callback->getState()) {
            $this->set($callback, CallbackInterface::STATE_COMPLETE);
        }
    }

    /**
     * @param CallbackInterface::STATE_* $state
     */
    private function set(CallbackInterface $callback, string $state): void
    {
        $callback->setState($state);

        $this->entityManager->persist($callback->getEntity());
        $this->entityManager->flush();
    }
}
