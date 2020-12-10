<?php

declare(strict_types=1);

namespace App\Messenger;

use App\Envelope\SerializableEnvelope;
use App\Exception\UnknownMessageClassException;
use App\Exception\UnknownMessageTypeException;
use App\Services\MessageFactory;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\NonSendableStampInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class JsonMessageSerializer implements SerializerInterface
{
    private MessageFactory $messageFactory;
    private Decoder $decoder;

    public function __construct(MessageFactory $messageFactory, Decoder $decoder)
    {
        $this->messageFactory = $messageFactory;
        $this->decoder = $decoder;
    }

    /**
     * @param array<mixed> $encodedEnvelope
     *
     * @return Envelope
     *
     * @throws UnknownMessageTypeException
     */
    public function decode(array $encodedEnvelope): Envelope
    {
        return $this->decoder->decode($encodedEnvelope);
    }

    /**
     * @param Envelope $envelope
     *
     * @return array<mixed>
     *
     * @throws UnknownMessageClassException
     */
    public function encode(Envelope $envelope): array
    {
        $envelope = $envelope->withoutStampsOfType(NonSendableStampInterface::class);

        return (new SerializableEnvelope($envelope))->encode();
    }
}
