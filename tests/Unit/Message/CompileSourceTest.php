<?php

declare(strict_types=1);

namespace App\Tests\Unit\Message;

use App\Message\CompileSource;
use PHPUnit\Framework\TestCase;

class CompileSourceTest extends TestCase
{
    public function testSerializeDeserialize()
    {
        $message = new CompileSource('Test/test.yml');

        self::assertEquals(
            $message,
            unserialize(serialize($message))
        );
    }
}
