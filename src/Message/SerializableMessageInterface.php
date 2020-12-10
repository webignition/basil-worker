<?php

declare(strict_types=1);

namespace App\Message;

interface SerializableMessageInterface extends \Serializable
{
    public function getType(): string;

    /**
     * @return array<string, mixed>
     */
    public function getPayload(): array;
}
