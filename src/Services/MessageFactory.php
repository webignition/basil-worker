<?php

declare(strict_types=1);

namespace App\Services;

use App\Exception\UnknownMessageTypeException;
use App\Message\JsonSerializableMessageInterface;

class MessageFactory
{
    /**
     * @var array<string, class-string>
     */
    private array $typeToMessageClassMap;

    public function __construct($typeToMessageClassMap)
    {
        $this->typeToMessageClassMap = $typeToMessageClassMap;
    }

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
        $messageClass = $this->typeToMessageClassMap[$type] ?? null;

        if (is_string($messageClass)) {
            $messageClassInterfaces = class_implements($messageClass);
            if (in_array(JsonSerializableMessageInterface::class, $messageClassInterfaces)) {
                return $messageClass::createFromArray($payload);
            }
        }

        throw new UnknownMessageTypeException($type);
    }
}
