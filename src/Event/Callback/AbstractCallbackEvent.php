<?php

declare(strict_types=1);

namespace App\Event\Callback;

use App\Entity\Callback\CallbackInterface;
use App\Event\FooCallbackEventInterface;
use Symfony\Contracts\EventDispatcher\Event;

abstract class AbstractCallbackEvent extends Event implements FooCallbackEventInterface
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
