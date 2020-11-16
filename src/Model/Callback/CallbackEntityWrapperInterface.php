<?php

declare(strict_types=1);

namespace App\Model\Callback;

use App\Entity\Callback\CallbackEntity;
use App\Entity\StorableCallbackInterface;

interface CallbackEntityWrapperInterface extends CallbackModelInterface, StorableCallbackInterface
{
    public function getEntity(): CallbackEntity;
}
