<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Callback\CallbackEntity;
use App\Entity\Callback\CallbackInterface;
use PHPUnit\Framework\TestCase;

class CallbackEntityTest extends TestCase
{
    public function testIncrementRetryCount(): void
    {
        $callback = CallbackEntity::create(CallbackInterface::TYPE_COMPILATION_FAILED, []);
        self::assertSame(0, $callback->getRetryCount());

        $callback->incrementRetryCount();
        self::assertSame(1, $callback->getRetryCount());

        $callback->incrementRetryCount();
        self::assertSame(2, $callback->getRetryCount());

        $callback->incrementRetryCount();
        self::assertSame(3, $callback->getRetryCount());
    }

    public function testHasState(): void
    {
        $callback = CallbackEntity::create(CallbackInterface::TYPE_COMPILATION_FAILED, []);
        self::assertTrue($callback->hasState(CallbackInterface::STATE_AWAITING));
        self::assertFalse($callback->hasState(CallbackInterface::STATE_COMPLETE));

        $callback->setState(CallbackInterface::STATE_COMPLETE);
        self::assertFalse($callback->hasState(CallbackInterface::STATE_AWAITING));
        self::assertTrue($callback->hasState(CallbackInterface::STATE_COMPLETE));
    }
}
