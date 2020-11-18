<?php

declare(strict_types=1);

namespace App\Model\BackoffStrategy;

class ExponentialBackoffStrategy implements BackoffStrategyInterface
{
    private int $window;

    public function __construct(int $window = 1000)
    {
        $this->window = $window;
    }

    public function getDelay(int $retryCount): int
    {
        $factor = (2 ** $retryCount) - 1;

        return $factor * $this->window;
    }
}
