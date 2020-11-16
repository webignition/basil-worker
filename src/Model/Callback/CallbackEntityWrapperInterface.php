<?php

declare(strict_types=1);

namespace App\Model\Callback;

use App\Entity\CallbackEntity;

interface CallbackEntityWrapperInterface extends CallbackModelInterface
{
    public function getEntity(): CallbackEntity;
}
