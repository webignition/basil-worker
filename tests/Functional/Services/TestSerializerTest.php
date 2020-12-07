<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Services\TestSerializer;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Services\InvokableFactory\TestSetup;
use App\Tests\Services\InvokableFactory\TestSetupInvokableFactory;
use App\Tests\Services\InvokableHandler;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class TestSerializerTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private TestSerializer $testSerializer;
    private InvokableHandler $invokableHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    /**
     * @dataProvider serializeDataProvider
     *
     * @param array<mixed> $expectedSerializedTest
     */
    public function testSerialize(InvokableInterface $setup, array $expectedSerializedTest)
    {
        $test = $this->invokableHandler->invoke($setup);

        self::assertSame(
            $expectedSerializedTest,
            $this->testSerializer->serialize($test)
        );
    }

    public function serializeDataProvider(): array
    {
        return [
            'with compiler source path, with compiler target path' => [
                'setup' => TestSetupInvokableFactory::setup(
                    (new TestSetup())
                        ->withSource('/app/source/Test/test.yml')
                        ->withTarget('/app/tests/GeneratedTest.php')
                ),
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
                'setup' => TestSetupInvokableFactory::setup(
                    (new TestSetup())
                        ->withSource('Test/test.yml')
                        ->withTarget('GeneratedTest.php')
                ),
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
