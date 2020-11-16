<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Callback\CallbackInterface;
use Psr\EventDispatcher\StoppableEventInterface;

interface FooCallbackEventInterface extends StoppableEventInterface
{
    public function getCallback(): CallbackInterface;
}
