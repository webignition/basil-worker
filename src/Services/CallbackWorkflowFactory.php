<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Workflow\CallbackWorkflow;
use App\Repository\CallbackRepository;

class CallbackWorkflowFactory
{
    private CallbackRepository $callbackRepository;

    public function __construct(CallbackRepository $callbackRepository)
    {
        $this->callbackRepository = $callbackRepository;
    }

    public function create(): CallbackWorkflow
    {
        return new CallbackWorkflow(
            $this->callbackRepository->count([]),
            $this->callbackRepository->getFinishedCount()
        );
    }
}
