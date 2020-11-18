<?php

declare(strict_types=1);

namespace App\Entity\Callback;

use App\Model\StampCollection;

interface StampedCallbackInterface extends CallbackInterface
{
    public function getStamps(): StampCollection;
}
