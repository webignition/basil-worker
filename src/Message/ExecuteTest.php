<?php

declare(strict_types=1);

namespace App\Message;

use webignition\JsonMessageSerializerBundle\Message\AbstractSerializableMessage;

class ExecuteTest extends AbstractSerializableMessage
{
    public const TYPE = 'execute-test';
    public const PAYLOAD_KEY_TEST_ID = 'test_id';

    private int $testId;

    public function __construct(int $testId)
    {
        $this->testId = $testId;
    }

    /**
     * @param array<mixed> $data
     *
     * @return self
     */
    public static function createFromArray(array $data): self
    {
        return new ExecuteTest((int) ($data[self::PAYLOAD_KEY_TEST_ID] ?? 0));
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
}
