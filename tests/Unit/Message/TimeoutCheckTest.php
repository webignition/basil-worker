<?php

declare(strict_types=1);

namespace App\Tests\Unit\Message;

use App\Message\TimeoutCheck;
use PHPUnit\Framework\TestCase;

class TimeoutCheckTest extends TestCase
{
    public function testSerializeDeserialize()
    {
        $message = new TimeoutCheck();

        self::assertEquals(
            $message,
            unserialize(serialize($message))
        );
    }
}
