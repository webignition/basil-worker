<?php

declare(strict_types=1);

namespace App\Model\BackoffStrategy;

interface BackoffStrategyInterface
{
    /**
     * @param int $retryCount
     *
     * @return int Delay in milliseconds
     */
    public function getDelay(int $retryCount): int;
}
