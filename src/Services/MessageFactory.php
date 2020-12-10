<?php

declare(strict_types=1);

namespace App\Services;

use App\Exception\UnknownMessageTypeException;
use App\Message\CompileSource;
use App\Message\ExecuteTest;
use App\Message\JsonSerializableMessageInterface;
use App\Message\SendCallback;
use App\Message\TimeoutCheck;

class MessageFactory
{
    /**
     * @param string $type
     * @param array<mixed> $payload
     *
     * @return JsonSerializableMessageInterface
     *
     * @throws UnknownMessageTypeException
     */
    public function create(string $type, array $payload): JsonSerializableMessageInterface
    {
        if (CompileSource::TYPE === $type) {
            return CompileSource::createFromArray($payload);
        }

        if (ExecuteTest::TYPE === $type) {
            return ExecuteTest::createFromArray($payload);
        }

        if (SendCallback::TYPE === $type) {
            return SendCallback::createFromArray($payload);
        }

        if (TimeoutCheck::TYPE === $type) {
            return TimeoutCheck::createFromArray($payload);
        }

        throw new UnknownMessageTypeException($type);
    }
}
