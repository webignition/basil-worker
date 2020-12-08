<?php

declare(strict_types=1);

namespace App\Event;

use Psr\EventDispatcher\StoppableEventInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;

interface CallbackEventInterface extends StoppableEventInterface
{
    public function getCallback(): CallbackInterface;
}
