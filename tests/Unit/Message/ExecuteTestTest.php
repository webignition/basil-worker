<?php

declare(strict_types=1);

namespace App\Tests\Unit\Message;

use App\Message\ExecuteTest;
use PHPUnit\Framework\TestCase;

class ExecuteTestTest extends TestCase
{
    private const TEST_ID = 7;

    private ExecuteTest $message;

    protected function setUp(): void
    {
        parent::setUp();

        $this->message = new ExecuteTest(self::TEST_ID);
    }

    public function testGetTestId()
    {
        self::assertSame(self::TEST_ID, $this->message->getTestId());
    }

    public function testGetType()
    {
        self::assertSame(ExecuteTest::TYPE, $this->message->getType());
    }

    public function testGetPayload()
    {
        self::assertSame(
            [
                'test_id' => $this->message->getTestId(),
            ],
            $this->message->getPayload()
        );
    }

    public function testJsonSerialize()
    {
        self::assertSame(
            [
                'type' => ExecuteTest::TYPE,
                'payload' => [
                    'test_id' => $this->message->getTestId(),
                ],
            ],
            $this->message->jsonSerialize()
        );
    }
}
