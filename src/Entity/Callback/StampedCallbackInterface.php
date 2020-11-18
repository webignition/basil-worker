<?php

declare(strict_types=1);

namespace App\Entity\Callback;

use Symfony\Component\Messenger\Stamp\StampInterface;

interface StampedCallbackInterface extends CallbackInterface
{
    /**
     * @return StampInterface[]
     */
    public function getStamps(): array;
}
