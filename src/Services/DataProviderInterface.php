<?php

namespace App\Services;

interface DataProviderInterface
{
    /**
     * @return array<mixed>
     */
    public function getData(): array;
}
