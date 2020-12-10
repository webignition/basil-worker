<?php

declare(strict_types=1);

namespace App\Tests\Unit\Envelope;

use App\Envelope\SerializableEnvelope;
use App\Message\CompileSource;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\DelayStamp;

class SerializableEnvelopeTest extends TestCase
{
    /**
     * @dataProvider encodeDataProvider
     *
     * @param SerializableEnvelope $envelope
     * @param array<mixed> $expectedSerializedEnvelope
     */
    public function testEncode(SerializableEnvelope $envelope, array $expectedSerializedEnvelope)
    {
        self::assertSame($expectedSerializedEnvelope, $envelope->encode());
    }

    public function encodeDataProvider(): array
    {
        $compileSourceMessage = new CompileSource('Test/test.yml');

        return [
            'compile source message, no stamps' => [
                'envelope' => (new SerializableEnvelope(
                    new Envelope($compileSourceMessage)
                )),
                'expectedSerializedEnvelope' => [
                    'body' => (string) json_encode($compileSourceMessage),
                ],
            ],
            'compile source message, has stamps' => [
                'envelope' => (new SerializableEnvelope(
                    new Envelope($compileSourceMessage, [new DelayStamp(100)])
                )),
                'expectedSerializedEnvelope' => [
                    'body' => (string) json_encode($compileSourceMessage),
                    'headers' => [
                        'stamps' => serialize([new DelayStamp(100)])
                    ],
                ],
            ],
        ];
    }
}
