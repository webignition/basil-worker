<?php

declare(strict_types=1);

namespace App\Message;

abstract class AbstractSerializableMessage implements JsonSerializableMessageInterface
{
    /**
     * @return array<mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            self::KEY_TYPE => $this->getType(),
            self::KEY_PAYLOAD => $this->getPayload(),
        ];
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
