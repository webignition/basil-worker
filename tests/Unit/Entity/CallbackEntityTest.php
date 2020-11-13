<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\ExecuteDocumentReceivedCallback;
use PHPUnit\Framework\TestCase;
use webignition\YamlDocument\Document;

class CallbackEntityTest extends TestCase
{
    public function testIncrementRetryCount()
    {
        $callback = ExecuteDocumentReceivedCallback::create(new Document(''));
        self::assertSame(0, $callback->getRetryCount());

        $callback->incrementRetryCount();
        self::assertSame(1, $callback->getRetryCount());

        $callback->incrementRetryCount();
        self::assertSame(2, $callback->getRetryCount());

        $callback->incrementRetryCount();
        self::assertSame(3, $callback->getRetryCount());
    }
}
