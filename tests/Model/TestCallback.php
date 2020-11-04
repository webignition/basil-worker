<?php

declare(strict_types=1);

namespace App\Tests\Model;

use App\Model\Callback\AbstractCallback;

class TestCallback extends AbstractCallback
{
    private const TYPE = 'test';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getData(): array
    {
        return [];
    }
}
