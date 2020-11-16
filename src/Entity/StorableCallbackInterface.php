<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Callback\CallbackEntity;

interface StorableCallbackInterface
{
    public function getId(): ?int;
    public function getEntity(): CallbackEntity;
}
