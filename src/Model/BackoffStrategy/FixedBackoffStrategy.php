<?php

declare(strict_types=1);

namespace App\Model\BackoffStrategy;

class FixedBackoffStrategy implements BackoffStrategyInterface
{
    private int $window;

    public function __construct(int $window)
    {
        $this->window = $window;
    }

    public function getDelay(int $retryCount): int
    {
        return $this->window;
    }
}
