<?php

declare(strict_types=1);

namespace App\Message;

class CompileSource extends AbstractSerializableMessage
{
    public const TYPE = 'compile-source';
    public const PAYLOAD_KEY_PATH = 'path';

    private string $path;

    public function __construct(string $source)
    {
        $this->path = $source;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getPayload(): array
    {
        return [
            self::PAYLOAD_KEY_PATH => $this->path,
        ];
    }

    public function unserialize($serialized): void
    {
        $path = $this->decodePayloadValue($serialized, self::PAYLOAD_KEY_PATH);
        $this->path = (string) ($path ?? '');
    }
}
