<?php

declare(strict_types=1);

namespace App\Model\Callback;

use App\Model\StampCollection;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;

interface StampedCallbackInterface extends CallbackInterface
{
    public function getStamps(): StampCollection;
}
