<?php

declare(strict_types=1);

namespace App\Entity\Callback;

use App\Model\BackoffStrategy\BackoffStrategyInterface;
use App\Model\BackoffStrategy\ExponentialBackoffStrategy;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\StampInterface;

class DelayedCallback extends AbstractCallbackWrapper implements StampedCallbackInterface
{
    private BackoffStrategyInterface $backoffStrategy;

    public function __construct(CallbackInterface $callback, BackoffStrategyInterface $backoffStrategy)
    {
        parent::__construct($callback);

        $this->backoffStrategy = $backoffStrategy;
    }

    public static function create(CallbackInterface $callback): self
    {
        return new DelayedCallback($callback, new ExponentialBackoffStrategy());
    }

    /**
     * @return StampInterface[]
     */
    public function getStamps(): array
    {
        $delay = $this->backoffStrategy->getDelay($this->getRetryCount());

        if (0 === $delay) {
            return [];
        }

        return [
            new DelayStamp($delay),
        ];
    }
}
