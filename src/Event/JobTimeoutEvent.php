<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Callback\CallbackInterface;
use App\Entity\Callback\JobTimeoutCallback;
use Symfony\Contracts\EventDispatcher\Event;

class JobTimeoutEvent extends Event implements CallbackEventInterface
{
    private int $jobMaximumDuration;
    private CallbackInterface $callback;

    public function __construct(int $jobMaximumDuration)
    {
        $this->jobMaximumDuration = $jobMaximumDuration;
        $this->callback = new JobTimeoutCallback($this->jobMaximumDuration);
    }

    public function getJobMaximumDuration(): int
    {
        return $this->jobMaximumDuration;
    }

    public function getCallback(): CallbackInterface
    {
        return $this->callback;
    }
}
