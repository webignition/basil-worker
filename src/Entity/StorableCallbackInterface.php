<?php

declare(strict_types=1);

namespace App\Entity;

interface StorableCallbackInterface
{
    public function getId(): ?int;
    public function getEntity(): CallbackEntity;
}
