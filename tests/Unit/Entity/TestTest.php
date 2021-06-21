<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use App\Entity\Callback\CallbackInterface;
use App\Entity\Test;
use App\Entity\TestConfiguration;

class TestTest extends TestCase
{
    public function testHasState(): void
    {
        $test = Test::create(
            \Mockery::mock(TestConfiguration::class),
            '',
            '',
            0,
            0
        );

        self::assertTrue($test->hasState(Test::STATE_AWAITING));
        self::assertFalse($test->hasState(Test::STATE_COMPLETE));

        $test->setState(CallbackInterface::STATE_COMPLETE);
        self::assertFalse($test->hasState(Test::STATE_AWAITING));
        self::assertTrue($test->hasState(Test::STATE_COMPLETE));
    }
}
