<?php

declare(strict_types=1);

namespace App\Model\Document;

class Step extends AbstractDocument
{
    private const KEY_STATUS = 'status';
    private const TYPE = 'step';
    private const STATUS_PASSED = 'passed';
    private const STATUS_FAILED = 'failed';

    public function isStep(): bool
    {
        return self::TYPE === $this->getType();
    }

    public function statusIsPassed(): bool
    {
        return $this->hasStatus(self::STATUS_PASSED);
    }

    public function statusIsFailed(): bool
    {
        return $this->hasStatus(self::STATUS_FAILED);
    }

    private function hasStatus(string $status): bool
    {
        return ($this->getData()[self::KEY_STATUS] ?? '') === $status;
    }
}
