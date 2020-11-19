<?php

declare(strict_types=1);

namespace App\Entity\Callback;

use App\Model\BackoffStrategy\BackoffStrategyInterface;
use App\Model\StampCollection;
use Symfony\Component\Messenger\Stamp\DelayStamp;

class DelayedCallback extends AbstractCallbackWrapper implements StampedCallbackInterface
{
    private BackoffStrategyInterface $backoffStrategy;

    public function __construct(CallbackInterface $callback, BackoffStrategyInterface $backoffStrategy)
    {
        parent::__construct($callback);

        $this->backoffStrategy = $backoffStrategy;
    }

    public function getStamps(): StampCollection
    {
        $delay = $this->backoffStrategy->getDelay($this->getRetryCount());
        $stamps = [];

        if (0 !== $delay) {
            $stamps[] = new DelayStamp($delay);
        }

        return new StampCollection($stamps);
    }
}
