<?php

declare(strict_types=1);

namespace App\Model\Callback;

abstract class AbstractCallback implements CallbackInterface
{
    private int $retryCount = 0;

    public function getRetryCount(): int
    {
        return $this->retryCount;
    }

    public function incrementRetryCount(): void
    {
        $this->retryCount++;
    }

    public function hasReachedRetryLimit(int $limit): bool
    {
        return false === ($this->retryCount < $limit);
    }
}
