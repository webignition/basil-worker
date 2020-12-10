<?php

declare(strict_types=1);

namespace App\Tests\Unit\Message;

use App\Message\ExecuteTest;
use PHPUnit\Framework\TestCase;

class ExecuteTestTest extends TestCase
{
    public function testSerializeDeserialize()
    {
        $message = new ExecuteTest(7);

        self::assertEquals(
            $message,
            unserialize(serialize($message))
        );
    }
}
