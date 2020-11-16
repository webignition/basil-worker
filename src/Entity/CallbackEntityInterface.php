<?php

declare(strict_types=1);

namespace App\Entity;

use App\Model\Callback\CallbackModelInterface;

interface CallbackEntityInterface extends CallbackModelInterface
{
    public function getId(): ?int;
}
