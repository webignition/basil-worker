<?php

declare(strict_types=1);

namespace App\Services\EntityStore;

use App\Repository\CallbackRepository;
use App\Entity\Callback\CallbackInterface;

class CallbackStore
{
    public function __construct(private CallbackRepository $repository)
    {
    }

    public function getFinishedCount(): int
    {
        return $this->repository->count([
            'state' => [
                CallbackInterface::STATE_FAILED,
                CallbackInterface::STATE_COMPLETE,
            ],
        ]);
    }

    /**
     * @param CallbackInterface::TYPE_* $type
     */
    public function getTypeCount(string $type): int
    {
        return $this->repository->count([
            'type' => $type,
        ]);
    }
}
