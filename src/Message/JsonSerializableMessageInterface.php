<?php

declare(strict_types=1);

namespace App\Message;

interface SerializableMessageInterface extends \JsonSerializable
{
    public const KEY_TYPE = 'type';
    public const KEY_PAYLOAD = 'payload';

    public function getType(): string;

    /**
     * @return array<string, mixed>
     */
    public function getPayload(): array;

    /**
     * @param array<mixed> $data
     *
     * @return self
     */
    public static function createFromArray(array $data): self;
}
