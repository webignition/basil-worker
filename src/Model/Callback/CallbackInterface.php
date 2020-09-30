<?php

declare(strict_types=1);

namespace App\Model\Callback;

interface CallbackInterface
{
    public function getType(): string;

    /**
     * @return array<mixed>
     */
    public function getData(): array;
}
