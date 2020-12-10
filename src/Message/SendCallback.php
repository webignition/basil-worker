<?php

declare(strict_types=1);

namespace App\Message;

use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;

class SendCallback extends AbstractSerializableMessage
{
    public const TYPE = 'send-callback';
    public const PAYLOAD_KEY_CALLBACK_ID = 'callback_id';

    private int $callbackId;

    public function __construct(CallbackInterface $callback)
    {
        $this->callbackId = (int) $callback->getId();
    }

    public function getCallbackId(): int
    {
        return $this->callbackId;
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getPayload(): array
    {
        return [
            self::PAYLOAD_KEY_CALLBACK_ID => $this->callbackId,
        ];
    }

    public function unserialize($serialized): void
    {
        $callbackId = $this->decodePayloadValue($serialized, self::PAYLOAD_KEY_CALLBACK_ID);
        $this->callbackId = (int) ($callbackId ?? 0);
    }
}
