<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Test;
use App\Entity\TestConfiguration;
use App\Services\TestFactory;
use App\Tests\AbstractBaseFunctionalTest;
use webignition\BasilCompilerModels\TestManifest;
use webignition\BasilModels\Test\Configuration;

class TestFactoryTest extends AbstractBaseFunctionalTest
{
    private TestFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $factory = self::$container->get(TestFactory::class);
        self::assertInstanceOf(TestFactory::class, $factory);

        if ($factory instanceof TestFactory) {
            $this->factory = $factory;
        }
    }

    /**
     * @dataProvider createFromTestManifestCollectionDataProvider
     *
     * @param TestManifest[] $manifests
     * @param Test[] $expectedTests
     */
    public function testCreateFromTestManifestCollection(array $manifests, array $expectedTests)
    {
        $tests = $this->factory->createFromManifestCollection($manifests);
        self::assertCount(count($expectedTests), $tests);

        foreach ($tests as $testIndex => $test) {
            self::assertIsInt($test->getId());
            self::assertGreaterThan(0, $test->getId());

            $expectedTest = $expectedTests[$testIndex] ?? null;
            self::assertInstanceOf(Test::class, $expectedTest);

            $configuration = $test->getConfiguration();
            self::assertIsInt($configuration->getId());
            self::assertGreaterThan(0, $configuration->getId());

            $this->assertTestEquals($expectedTest, $test);
        }
    }

    public function createFromTestManifestCollectionDataProvider(): array
    {
        $chromeTestManifest = new TestManifest(
            new Configuration('chrome', 'http://example.com'),
            'Tests/chrome_test.yml',
            '/app/tests/GeneratedChromeTest.php',
            2
        );

        $firefoxTestManifest = new TestManifest(
            new Configuration('firefox', 'http://example.com'),
            'Tests/firefox_test.yml',
            '/app/tests/GeneratedFirefoxTest.php',
            3
        );

        $expectedChromeTest = Test::create(
            TestConfiguration::create('chrome', 'http://example.com'),
            'Tests/chrome_test.yml',
            '/app/tests/GeneratedChromeTest.php',
            2,
            1
        );

        $expectedFirefoxTest = Test::create(
            TestConfiguration::create('firefox', 'http://example.com'),
            'Tests/firefox_test.yml',
            '/app/tests/GeneratedFirefoxTest.php',
            3,
            2
        );

        return [
            'empty' => [
                'manifests' => [],
                'expectedTests' => [],
            ],
            'single manifest' => [
                'manifests' => [
                    $chromeTestManifest,
                ],
                'expectedTests' => [
                    $expectedChromeTest
                ],
            ],
            'two manifests' => [
                'manifests' => [
                    $chromeTestManifest,
                    $firefoxTestManifest,
                ],
                'expectedTests' => [
                    $expectedChromeTest,
                    $expectedFirefoxTest,
                ],
            ],
        ];
    }

    private function assertTestEquals(Test $expected, Test $actual): void
    {
        $this->assertTestConfigurationEquals($expected->getConfiguration(), $actual->getConfiguration());
        self::assertSame($expected->getSource(), $actual->getSource());
        self::assertSame($expected->getTarget(), $actual->getTarget());
        self::assertSame($expected->getState(), $actual->getState());
        self::assertSame($expected->getStepCount(), $actual->getStepCount());
        self::assertSame($expected->getPosition(), $actual->getPosition());
    }

    private function assertTestConfigurationEquals(TestConfiguration $expected, TestConfiguration $actual): void
    {
        self::assertSame($expected->getBrowser(), $actual->getBrowser());
        self::assertSame($expected->getUrl(), $actual->getUrl());
    }
}
