<?php

declare(strict_types=1);

namespace App\Message;

use App\Model\Callback\CallbackInterface;

class SendCallback
{
    private CallbackInterface $callback;

    public function __construct(CallbackInterface $callback)
    {
        $this->callback = $callback;
    }

    public function getCallback(): CallbackInterface
    {
        return $this->callback;
    }
}
