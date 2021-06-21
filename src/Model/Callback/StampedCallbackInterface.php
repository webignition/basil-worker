<?php

declare(strict_types=1);

namespace App\Model\Callback;

use App\Entity\Callback\CallbackInterface;
use App\Model\StampCollection;

interface StampedCallbackInterface extends CallbackInterface
{
    public function getStamps(): StampCollection;
}
