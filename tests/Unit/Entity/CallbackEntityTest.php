<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\CallbackEntity;
use PHPUnit\Framework\TestCase;

class CallbackEntityTest extends TestCase
{
    public function testIncrementRetryCount()
    {
        $callback = CallbackEntity::create('type', []);
        self::assertSame(0, $callback->getRetryCount());

        $callback->incrementRetryCount();
        self::assertSame(1, $callback->getRetryCount());

        $callback->incrementRetryCount();
        self::assertSame(2, $callback->getRetryCount());

        $callback->incrementRetryCount();
        self::assertSame(3, $callback->getRetryCount());
    }
}
