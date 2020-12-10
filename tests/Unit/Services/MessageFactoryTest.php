<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Message\CompileSource;
use App\Message\ExecuteTest;
use App\Message\JsonSerializableMessageInterface;
use App\Message\SendCallback;
use App\Message\TimeoutCheck;
use App\Services\MessageFactory;
use PHPUnit\Framework\TestCase;

class MessageFactoryTest extends TestCase
{
    private MessageFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new MessageFactory([
            CompileSource::TYPE => CompileSource::class,
            ExecuteTest::TYPE => ExecuteTest::class,
            SendCallback::TYPE => SendCallback::class,
            TimeoutCheck::TYPE => TimeoutCheck::class,
        ]);
    }

    /**
     * @dataProvider createDataProvider
     *
     * @param array<mixed> $payload
     */
    public function testCreate(string $type, array $payload, JsonSerializableMessageInterface $expectedMessage)
    {
        self::assertEquals($expectedMessage, $this->factory->create($type, $payload));
    }

    public function createDataProvider(): array
    {
        return [
            'compile source' => [
                'type' => CompileSource::TYPE,
                'payload' => [
                    'path' => 'Test/test.yml',
                ],
                'expectedMessage' => new CompileSource('Test/test.yml'),
            ],
            'execute test' => [
                'type' => ExecuteTest::TYPE,
                'payload' => [
                    'test_id' => 3,
                ],
                'expectedMessage' => new ExecuteTest(3),
            ],
            'send callback' => [
                'type' => SendCallback::TYPE,
                'payload' => [
                    'callback_id' => 5,
                ],
                'expectedMessage' => new SendCallback(5),
            ],
            'timeout check' => [
                'type' => TimeoutCheck::TYPE,
                'payload' => [],
                'expectedMessage' => new TimeoutCheck(),
            ],
        ];
    }
}
