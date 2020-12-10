<?php

declare(strict_types=1);

namespace App\Message;

abstract class AbstractSerializableMessage implements SerializableMessageInterface
{
    public const KEY_TYPE = 'type';
    public const KEY_PAYLOAD = 'payload';

    public function serialize(): string
    {
        return (string) json_encode([
            self::KEY_TYPE => $this->getType(),
            self::KEY_PAYLOAD => $this->getPayload(),
        ]);
    }

    /**
     * @return mixed
     */
    protected function decodePayloadValue(string $serialized, string $key)
    {
        $payloadData = $this->decodePayloadData($serialized);

        return $payloadData[$key] ?? null;
    }

    /**
     * @return array<mixed>
     */
    private function decodePayloadData(string $serialized): array
    {
        $data = json_decode($serialized, true);

        $payloadData = $data[self::KEY_PAYLOAD] ?? [];
        if (!is_array($payloadData)) {
            $payloadData = [];
        }

        return $payloadData;
    }
}
