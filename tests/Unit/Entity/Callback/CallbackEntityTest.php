<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity\Callback;

use App\Entity\Callback\CallbackEntity;
use App\Entity\Callback\CallbackInterface;
use PHPUnit\Framework\TestCase;

class CallbackEntityTest extends TestCase
{
    public function testIncrementRetryCount()
    {
        $callback = CallbackEntity::create(CallbackInterface::TYPE_COMPILE_FAILURE, []);
        self::assertSame(0, $callback->getRetryCount());

        $callback->incrementRetryCount();
        self::assertSame(1, $callback->getRetryCount());

        $callback->incrementRetryCount();
        self::assertSame(2, $callback->getRetryCount());

        $callback->incrementRetryCount();
        self::assertSame(3, $callback->getRetryCount());
    }
}
