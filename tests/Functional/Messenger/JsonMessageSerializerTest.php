<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageHandler;

use App\Message\CompileSource;
use App\Message\ExecuteTest;
use App\Message\SendCallback;
use App\Message\TimeoutCheck;
use App\Messenger\JsonMessageSerializer;
use App\Tests\AbstractBaseFunctionalTest;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class JsonMessageSerializerTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;
    use TestClassServicePropertyInjectorTrait;

    private JsonMessageSerializer $serializer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    /**
     * @dataProvider encodeDecodeDataProvider
     */
    public function testEncodeDecode(Envelope $envelope)
    {
        $encodedEnvelope = $this->serializer->encode($envelope);
        $decodedEnvelope = $this->serializer->decode($encodedEnvelope);

        self::assertEquals($envelope, $decodedEnvelope);
    }

    public function encodeDecodeDataProvider(): array
    {
        return [
            'compile source' => [
                'envelope' => new Envelope(
                    new CompileSource('Test/test.yml'),
                )
            ],
            'execute test' => [
                'envelope' => new Envelope(
                    new ExecuteTest(7),
                )
            ],
            'send callback' => [
                'envelope' => new Envelope(
                    new SendCallback(4),
                )
            ],
            'send callback, delayed' => [
                'envelope' => new Envelope(
                    new SendCallback(4),
                    [
                        new DelayStamp(1000),
                    ]
                )
            ],
            'timeout check' => [
                'envelope' => new Envelope(
                    new TimeoutCheck(),
                )
            ],
        ];
    }
}
