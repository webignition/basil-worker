<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\CallbackEntityInterface;
use App\Model\Callback\CallbackModelInterface;

class CallbackStateMutator
{
    private CallbackStore $callbackStore;

    public function __construct(CallbackStore $callbackStore)
    {
        $this->callbackStore = $callbackStore;
    }

    public function setQueued(CallbackEntityInterface $callback): void
    {
        $this->setStateIfState($callback, CallbackModelInterface::STATE_AWAITING, CallbackModelInterface::STATE_QUEUED);
    }

    public function setSending(CallbackEntityInterface $callback): void
    {
        $this->setStateIfState($callback, CallbackModelInterface::STATE_QUEUED, CallbackModelInterface::STATE_SENDING);
    }

    public function setFailed(CallbackEntityInterface $callback): void
    {
        $this->setStateIfState($callback, CallbackModelInterface::STATE_SENDING, CallbackModelInterface::STATE_FAILED);
    }

    public function setComplete(CallbackEntityInterface $callback): void
    {
        $this->setStateIfState(
            $callback,
            CallbackModelInterface::STATE_SENDING,
            CallbackModelInterface::STATE_COMPLETE
        );
    }

    /**
     * @param CallbackEntityInterface $callback
     * @param CallbackModelInterface::STATE_* $currentState
     * @param CallbackModelInterface::STATE_* $newState
     */
    private function setStateIfState(CallbackEntityInterface $callback, string $currentState, string $newState): void
    {
        if ($currentState === $callback->getState()) {
            $this->set($callback, $newState);
        }
    }

    /**
     * @param CallbackEntityInterface $callback
     * @param CallbackModelInterface::STATE_* $state
     */
    private function set(CallbackEntityInterface $callback, string $state): void
    {
        $callback->setState($state);
        $this->callbackStore->store($callback);
    }
}
