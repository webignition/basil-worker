<?php

declare(strict_types=1);

namespace App\Envelope;

interface JsonSerializableEnvelopeInterface extends \JsonSerializable
{
    public const KEY_BODY = 'body';
    public const KEY_HEADERS = 'headers';
    public const KEY_HEADER_STAMPS = 'stamps';
}
