<?php

declare(strict_types=1);

namespace App\Model\Callback;

interface IdentifiedCallbackInterface extends CallbackModelInterface
{
    public function getId(): ?int;
}
