<?php

declare(strict_types=1);

namespace App\Services;

use App\Repository\CallbackRepository;

class CallbackState
{
    public const STATE_AWAITING = 'awaiting';
    public const STATE_RUNNING = 'running';
    public const STATE_COMPLETE = 'complete';

    private CallbackRepository $callbackRepository;

    public function __construct(CallbackRepository $callbackRepository)
    {
        $this->callbackRepository = $callbackRepository;
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
        $callbackCount = $this->callbackRepository->count([]);
        $finishedCallbackCount = $this->callbackRepository->getFinishedCount();

        if (0 === $callbackCount) {
            return self::STATE_AWAITING;
        }

        return $finishedCallbackCount === $callbackCount
            ? self::STATE_COMPLETE
            : self::STATE_RUNNING;
    }
}
