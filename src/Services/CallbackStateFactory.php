<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\CallbackState;
use App\Repository\CallbackRepository;

class CallbackStateFactory
{
    private CallbackRepository $callbackRepository;

    public function __construct(CallbackRepository $callbackRepository)
    {
        $this->callbackRepository = $callbackRepository;
    }

    public function create(): CallbackState
    {
        $callbackCount = $this->callbackRepository->count([]);
        $finishedCallbackCount = $this->callbackRepository->getFinishedCount();

        if (0 === $callbackCount) {
            return new CallbackState(CallbackState::STATE_AWAITING);
        }

        $state = $finishedCallbackCount === $callbackCount
            ? CallbackState::STATE_COMPLETE
            : CallbackState::STATE_RUNNING;

        return new CallbackState($state);
    }
}
