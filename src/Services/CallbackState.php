<?php

declare(strict_types=1);

namespace App\Services;

use webignition\BasilWorker\PersistenceBundle\Services\Repository\CallbackRepository;
use webignition\BasilWorker\PersistenceBundle\Services\Store\CallbackStore;

class CallbackState
{
    public const STATE_AWAITING = 'awaiting';
    public const STATE_RUNNING = 'running';
    public const STATE_COMPLETE = 'complete';

    private CallbackStore $callbackStore;
    private CallbackRepository $repository;

    public function __construct(CallbackStore $callbackStore, CallbackRepository $repository)
    {
        $this->callbackStore = $callbackStore;
        $this->repository = $repository;
    }

    /**
     * @param CallbackState::STATE_* ...$states
     *
     * @return bool
     */
    public function is(...$states): bool
    {
        $states = array_filter($states, function ($item) {
            return is_string($item);
        });

        return in_array($this->getCurrentState(), $states);
    }

    /**
     * @return CallbackState::STATE_*
     */
    private function getCurrentState(): string
    {
        $callbackCount = $this->repository->count([]);
        $finishedCallbackCount = $this->callbackStore->getFinishedCount();

        if (0 === $callbackCount) {
            return self::STATE_AWAITING;
        }

        return $finishedCallbackCount === $callbackCount
            ? self::STATE_COMPLETE
            : self::STATE_RUNNING;
    }
}
