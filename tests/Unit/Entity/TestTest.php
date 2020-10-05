<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Test;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use webignition\BasilCompilerModels\TestManifest;
use webignition\BasilModels\Test\Configuration;

class TestTest extends TestCase
{
    use MockeryPHPUnitIntegration;

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
        $manifest = new TestManifest(
            new Configuration('chrome', 'http://example.com'),
            'Test/test1.yml',
            'generated/Generatedfc66338eaf47ef8bb65727705cdee990.php',
            2
        );

        $test = Test::create(
            'Test/test1.yml',
            $manifest,
            'manifests/manifest-test1.yml',
            1
        );

        $test->setManifest($manifest);

        return [
            'default' => [
                'test' => $test,
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
        $manifest = \Mockery::mock(TestManifest::class);

        $test = Test::create('Test/test1.yml', $manifest, 'manifests/manifest-test1.yml', 1);

        self::assertSame(Test::STATE_AWAITING, $test->getState());

        $test->setState(Test::STATE_RUNNING);
        self::assertSame(Test::STATE_RUNNING, $test->getState());
    }
}
