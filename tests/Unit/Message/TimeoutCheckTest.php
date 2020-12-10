<?php

declare(strict_types=1);

namespace App\Tests\Unit\Message;

use App\Message\TimeoutCheck;
use PHPUnit\Framework\TestCase;

class TimeoutCheckTest extends TestCase
{
    private TimeoutCheck $message;

    protected function setUp(): void
    {
        parent::setUp();

        $this->message = new TimeoutCheck();
    }

    public function testGetType()
    {
        self::assertSame(TimeoutCheck::TYPE, $this->message->getType());
    }

    public function testGetPayload()
    {
        self::assertSame(
            [],
            $this->message->getPayload()
        );
    }

    public function testJsonSerialize()
    {
        self::assertSame(
            [
                'type' => TimeoutCheck::TYPE,
                'payload' => [],
            ],
            $this->message->jsonSerialize()
        );
    }
}
