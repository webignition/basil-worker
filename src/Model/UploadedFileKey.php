<?php

declare(strict_types=1);

namespace App\Model;

class UploadedFileKey
{
    private string $key;

    public function __construct(string $key)
    {
        $this->key = $key;
    }

    public static function fromEncodedKey(string $encodedKey): self
    {
        return new UploadedFileKey(base64_decode($encodedKey));
    }

    public function encode(): string
    {
        return base64_encode($this->key);
    }

    public function __toString(): string
    {
        return $this->key;
    }
}
