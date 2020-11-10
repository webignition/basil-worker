<?php

declare(strict_types=1);

namespace App\Event\Callback;

use App\Event\CallbackEventInterface;
use App\Model\Callback\CallbackInterface;
use Symfony\Contracts\EventDispatcher\Event;

abstract class AbstractCallbackEvent extends Event implements CallbackEventInterface
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
