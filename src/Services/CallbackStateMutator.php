<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\CallbackEntity;

class CallbackStateMutator
{
    private CallbackStore $callbackStore;

    public function __construct(CallbackStore $callbackStore)
    {
        $this->callbackStore = $callbackStore;
    }

    public function setQueued(CallbackEntity $callback): void
    {
        $this->setStateIfState($callback, CallbackEntity::STATE_AWAITING, CallbackEntity::STATE_QUEUED);
    }

    public function setSending(CallbackEntity $callback): void
    {
        $this->setStateIfState($callback, CallbackEntity::STATE_QUEUED, CallbackEntity::STATE_SENDING);
    }

    public function setFailed(CallbackEntity $callback): void
    {
        $this->setStateIfState($callback, CallbackEntity::STATE_SENDING, CallbackEntity::STATE_FAILED);
    }

    public function setComplete(CallbackEntity $callback): void
    {
        $this->setStateIfState($callback, CallbackEntity::STATE_SENDING, CallbackEntity::STATE_COMPLETE);
    }

    /**
     * @param CallbackEntity $callback
     * @param CallbackEntity::STATE_* $currentState
     * @param CallbackEntity::STATE_* $newState
     */
    private function setStateIfState(CallbackEntity $callback, string $currentState, string $newState): void
    {
        if ($currentState === $callback->getState()) {
            $this->set($callback, $newState);
        }
    }

    /**
     * @param CallbackEntity $callback
     * @param CallbackEntity::STATE_* $state
     */
    private function set(CallbackEntity $callback, string $state): void
    {
        $callback->setState($state);
        $this->callbackStore->store($callback);
    }
}
