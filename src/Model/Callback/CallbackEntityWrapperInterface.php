<?php

declare(strict_types=1);

namespace App\Model\Callback;

use App\Entity\CallbackEntity;
use App\Entity\StorableCallbackInterface;

interface CallbackEntityWrapperInterface extends IdentifiedCallbackInterface, StorableCallbackInterface
{
    public function getEntity(): CallbackEntity;
}
