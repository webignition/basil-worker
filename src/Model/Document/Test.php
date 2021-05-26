<?php

declare(strict_types=1);

namespace App\Model\Document;

class Test extends AbstractDocument
{
    public const KEY_PATH = 'path';
    private const TYPE = 'test';

    public function isTest(): bool
    {
        return self::TYPE === $this->getType();
    }

    public function getPath(): string
    {
        return $this->getData()[self::KEY_PATH] ?? '';
    }
}
