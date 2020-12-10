<?php

declare(strict_types=1);

namespace App\Message;

class ExecuteTest extends AbstractSerializableMessage
{
    public const TYPE = 'execute-test';
    public const PAYLOAD_KEY_TEST_ID = 'test_id';

    private int $testId;

    public function __construct(int $testId)
    {
        $this->testId = $testId;
    }

    public function getTestId(): int
    {
        return $this->testId;
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getPayload(): array
    {
        return [
            self::PAYLOAD_KEY_TEST_ID => $this->testId,
        ];
    }

    public function unserialize($serialized): void
    {
        $testId = $this->decodePayloadValue($serialized, self::PAYLOAD_KEY_TEST_ID);
        $this->testId = (int) ($testId ?? 0);
    }
}
