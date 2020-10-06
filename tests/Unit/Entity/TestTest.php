<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Test;
use App\Entity\TestConfiguration;
use PHPUnit\Framework\TestCase;

class TestTest extends TestCase
{
    /**
     * @dataProvider jsonSerializeDataProvider
     *
     * @param Test $test
     * @param array<mixed> $expectedSerializedTest
     */
    public function testJsonSerialize(Test $test, array $expectedSerializedTest)
    {
        self::assertSame($expectedSerializedTest, $test->jsonSerialize());
    }

    public function jsonSerializeDataProvider(): array
    {
        return [
            'default' => [
                'test' => Test::create(
                    TestConfiguration::create('chrome', 'http://example.com'),
                    'Test/test1.yml',
                    'generated/Generatedfc66338eaf47ef8bb65727705cdee990.php',
                    2,
                    1
                ),
                'expectedSerializedTest' => [
                    'configuration' => [
                        'browser' => 'chrome',
                        'url' => 'http://example.com',
                    ],
                    'source' => 'Test/test1.yml',
                    'target' => 'generated/Generatedfc66338eaf47ef8bb65727705cdee990.php',
                    'step_count' => 2,
                    'state' => 'awaiting',
                    'position' => 1,
                ],
            ],
        ];
    }

    public function testSetState()
    {
        $test = Test::create(
            TestConfiguration::create('chrome', 'http://example.com'),
            'Test/test1.yml',
            'generated/Generatedfc66338eaf47ef8bb65727705cdee990.php',
            3,
            1
        );

        self::assertSame(Test::STATE_AWAITING, $test->getState());

        $test->setState(Test::STATE_RUNNING);
        self::assertSame(Test::STATE_RUNNING, $test->getState());
    }
}
