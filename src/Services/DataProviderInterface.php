<?php

declare(strict_types=1);

namespace App\Services;

interface DataProviderInterface
{
    /**
     * @return array<mixed>
     */
    public function getData(): array;
}
