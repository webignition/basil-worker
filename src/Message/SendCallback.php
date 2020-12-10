<?php

declare(strict_types=1);

namespace App\Message;

class SendCallback
{
    private int $callbackId;

    public function __construct(int $callbackId)
    {
        $this->callbackId = $callbackId;
    }

    public function getCallbackId(): int
    {
        return $this->callbackId;
    }
}
