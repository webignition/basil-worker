<?php

declare(strict_types=1);

namespace App\Model\Callback;

interface CallbackInterface
{
    public function getRetryCount(): int;
    public function incrementRetryCount(): void;
    public function hasReachedRetryLimit(int $limit): bool;

    public function getType(): string;

    /**
     * @return array<mixed>
     */
    public function getData(): array;
}
