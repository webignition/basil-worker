<?php

declare(strict_types=1);

namespace App\Message;

use webignition\SymfonyMessengerMessageDispatcher\Message\RetryableMessageInterface;

class SendCallbackMessage implements RetryableMessageInterface
{
    public function __construct(
        private int $callbackId,
        private int $retryCount = 0,
    ) {
    }

    public function getCallbackId(): int
    {
        return $this->callbackId;
    }

    public function getRetryCount(): int
    {
        return $this->retryCount;
    }
}
