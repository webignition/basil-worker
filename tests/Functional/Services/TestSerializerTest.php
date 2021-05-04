<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Services\TestSerializer;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Model\TestSetup;
use App\Tests\Services\TestTestFactory;

class TestSerializerTest extends AbstractBaseFunctionalTest
{
    private TestSerializer $testSerializer;
    private TestTestFactory $testTestFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $testSerializer = self::$container->get(TestSerializer::class);
        \assert($testSerializer instanceof TestSerializer);
        $this->testSerializer = $testSerializer;

        $testTestFactory = self::$container->get(TestTestFactory::class);
        \assert($testTestFactory instanceof TestTestFactory);
        $this->testTestFactory = $testTestFactory;
    }

    /**
     * @dataProvider serializeDataProvider
     *
     * @param array<mixed> $expectedSerializedTest
     */
    public function testSerialize(TestSetup $setup, array $expectedSerializedTest): void
    {
        $test = $this->testTestFactory->create($setup);

        self::assertSame(
            $expectedSerializedTest,
            $this->testSerializer->serialize($test)
        );
    }

    /**
     * @return array[]
     */
    public function serializeDataProvider(): array
    {
        return [
            'with compiler source path, with compiler target path' => [
                'setup' => (new TestSetup())
                    ->withSource('/app/source/Test/test.yml')
                    ->withTarget('/app/tests/GeneratedTest.php'),
                'expectedSerializedTest' => [
                    'configuration' => [
                        'browser' => 'chrome',
                        'url' => 'http://example.com',
                    ],
                    'source' => 'Test/test.yml',
                    'target' => 'GeneratedTest.php',
                    'step_count' => 1,
                    'state' => 'awaiting',
                    'position' => 1,
                ],
            ],
            'without compiler source path, without compiler target path' => [
                'setup' => (new TestSetup())
                    ->withSource('Test/test.yml')
                    ->withTarget('GeneratedTest.php'),
                'expectedSerializedTest' => [
                    'configuration' => [
                        'browser' => 'chrome',
                        'url' => 'http://example.com',
                    ],
                    'source' => 'Test/test.yml',
                    'target' => 'GeneratedTest.php',
                    'step_count' => 1,
                    'state' => 'awaiting',
                    'position' => 1,
                ],
            ],
        ];
    }
}
