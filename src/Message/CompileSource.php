<?php

declare(strict_types=1);

namespace App\Message;

class CompileSource extends AbstractSerializableMessage
{
    public const TYPE = 'compile-source';
    public const PAYLOAD_KEY_PATH = 'path';

    private string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    /**
     * @param array<mixed> $data
     *
     * @return self
     */
    public static function createFromArray(array $data): self
    {
        return new CompileSource((string) ($data[self::PAYLOAD_KEY_PATH] ?? ''));
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
}
