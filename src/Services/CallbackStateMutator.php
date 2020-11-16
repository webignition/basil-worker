<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Callback\CallbackInterface;

class CallbackStateMutator
{
    private CallbackStore $callbackStore;

    public function __construct(CallbackStore $callbackStore)
    {
        $this->callbackStore = $callbackStore;
    }

    public function setQueued(CallbackInterface $callback): void
    {
        if (in_array($callback->getState(), [CallbackInterface::STATE_AWAITING, CallbackInterface::STATE_SENDING])) {
            $this->set($callback, CallbackInterface::STATE_QUEUED);
        }
    }

    public function setSending(CallbackInterface $callback): void
    {
        $this->setStateIfState($callback, CallbackInterface::STATE_QUEUED, CallbackInterface::STATE_SENDING);
    }

    public function setFailed(CallbackInterface $callback): void
    {
        $this->setStateIfState($callback, CallbackInterface::STATE_SENDING, CallbackInterface::STATE_FAILED);
    }

    public function setComplete(CallbackInterface $callback): void
    {
        $this->setStateIfState(
            $callback,
            CallbackInterface::STATE_SENDING,
            CallbackInterface::STATE_COMPLETE
        );
    }

    /**
     * @param CallbackInterface $callback
     * @param CallbackInterface::STATE_* $currentState
     * @param CallbackInterface::STATE_* $newState
     */
    private function setStateIfState(CallbackInterface $callback, string $currentState, string $newState): void
    {
        if ($currentState === $callback->getState()) {
            $this->set($callback, $newState);
        }
    }

    /**
     * @param CallbackInterface $callback
     * @param CallbackInterface::STATE_* $state
     */
    private function set(CallbackInterface $callback, string $state): void
    {
        $callback->setState($state);
        $this->callbackStore->store($callback);
    }
}
