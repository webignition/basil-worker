<?php

declare(strict_types=1);

namespace App\Message;

use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;

class SendCallback
{
    private int $callbackId;

    public function __construct(CallbackInterface $callback)
    {
        $this->callbackId = (int) $callback->getId();
    }

    public function getCallbackId(): int
    {
        return $this->callbackId;
    }
}
