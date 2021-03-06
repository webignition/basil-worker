<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Test;
use App\Entity\TestConfiguration;
use App\Event\SourceCompilation\PassedEvent;
use App\Repository\TestRepository;
use App\Services\TestFactory;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\MockSuiteManifest;
use Psr\EventDispatcher\EventDispatcherInterface;
use webignition\BasilCompilerModels\SuiteManifest;
use webignition\BasilCompilerModels\TestManifest;
use webignition\BasilModels\Test\Configuration;

class TestFactoryTest extends AbstractBaseFunctionalTest
{
    private TestFactory $factory;
    private EventDispatcherInterface $eventDispatcher;

    /**
     * @var array<string, TestManifest>
     */
    private array $testManifests;

    /**
     * @var array<string, Test>
     */
    private array $tests;

    protected function setUp(): void
    {
        parent::setUp();

        $testFactory = self::$container->get(TestFactory::class);
        \assert($testFactory instanceof TestFactory);
        $this->factory = $testFactory;

        $eventDispatcher = self::$container->get(EventDispatcherInterface::class);
        \assert($eventDispatcher instanceof EventDispatcherInterface);
        $this->eventDispatcher = $eventDispatcher;

        $this->testManifests = [
            'chrome' => new TestManifest(
                new Configuration('chrome', 'http://example.com'),
                'Tests/chrome_test.yml',
                '/app/tests/GeneratedChromeTest.php',
                2
            ),
            'firefox' => new TestManifest(
                new Configuration('firefox', 'http://example.com'),
                'Tests/firefox_test.yml',
                '/app/tests/GeneratedFirefoxTest.php',
                3
            )
        ];

        $this->tests = [
            'chrome' => Test::create(
                TestConfiguration::create('chrome', 'http://example.com'),
                'Tests/chrome_test.yml',
                '/app/tests/GeneratedChromeTest.php',
                2,
                1
            ),
            'firefox' => Test::create(
                TestConfiguration::create('firefox', 'http://example.com'),
                'Tests/firefox_test.yml',
                '/app/tests/GeneratedFirefoxTest.php',
                3,
                2
            ),
        ];
    }

    /**
     * @dataProvider createFromTestManifestCollectionDataProvider
     *
     * @param string[] $manifestKeys
     * @param string[] $expectedTestKeys
     */
    public function testCreateFromTestManifestCollection(array $manifestKeys, array $expectedTestKeys): void
    {
        $manifests = $this->createTestManifestCollection($manifestKeys);
        $expectedTests = $this->createExpectedTestCollection($expectedTestKeys);

        $tests = $this->factory->createFromManifestCollection($manifests);
        self::assertCount(count($expectedTests), $tests);

        foreach ($tests as $testIndex => $test) {
            $this->assertCreatedTest($test, $expectedTests[$testIndex] ?? null);
        }
    }

    /**
     * @return array[]
     */
    public function createFromTestManifestCollectionDataProvider(): array
    {
        return [
            'empty' => [
                'manifestKeys' => [],
                'expectedTestKeys' => [],
            ],
            'single manifest' => [
                'manifestKeys' => [
                    'chrome',
                ],
                'expectedTestKeys' => [
                    'chrome',
                ],
            ],
            'two manifests' => [
                'manifestKeys' => [
                    'chrome',
                    'firefox',
                ],
                'expectedTestKeys' => [
                    'chrome',
                    'firefox',
                ],
            ],
        ];
    }

    public function testCreateFromSourceCompileSuccessEvent(): void
    {
        $this->doSourceCompileSuccessEventDrivenTest(function (SuiteManifest $suiteManifest) {
            $event = new PassedEvent('/app/source/Test/test.yml', $suiteManifest);

            return $this->factory->createFromSourceCompileSuccessEvent($event);
        });
    }

    public function testSubscribesToSourceCompileSuccessEvent(): void
    {
        $this->doSourceCompileSuccessEventDrivenTest(function (SuiteManifest $suiteManifest) {
            $event = new PassedEvent('/app/source/Test/test.yml', $suiteManifest);
            $this->eventDispatcher->dispatch($event);

            $testRepository = self::$container->get(TestRepository::class);
            \assert($testRepository instanceof TestRepository);

            return $testRepository->findAll();
        });
    }

    private function doSourceCompileSuccessEventDrivenTest(callable $callable): void
    {
        $suiteManifest = (new MockSuiteManifest())
            ->withGetTestManifestsCall(
                $this->createTestManifestCollection(['chrome', 'firefox'])
            )
            ->getMock()
        ;

        $tests = $callable($suiteManifest);

        $expectedTests = $this->createExpectedTestCollection(['chrome', 'firefox']);
        self::assertCount(count($expectedTests), $tests);

        foreach ($tests as $testIndex => $test) {
            $this->assertCreatedTest($test, $expectedTests[$testIndex] ?? null);
        }
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

    private function assertCreatedTest(Test $test, ?Test $expectedTest): void
    {
        self::assertIsInt($test->getId());
        self::assertGreaterThan(0, $test->getId());
        self::assertInstanceOf(Test::class, $expectedTest);

        $configuration = $test->getConfiguration();
        self::assertIsInt($configuration->getId());
        self::assertGreaterThan(0, $configuration->getId());

        $this->assertTestEquals($expectedTest, $test);
    }

    private function assertTestConfigurationEquals(TestConfiguration $expected, TestConfiguration $actual): void
    {
        self::assertSame($expected->getBrowser(), $actual->getBrowser());
        self::assertSame($expected->getUrl(), $actual->getUrl());
    }

    /**
     * @param string[] $manifestKeys
     *
     * @return TestManifest[]
     */
    private function createTestManifestCollection(array $manifestKeys): array
    {
        $manifests = [];
        foreach ($manifestKeys as $manifestKey) {
            $manifest = $this->testManifests[$manifestKey] ?? null;
            if ($manifest instanceof TestManifest) {
                $manifests[] = $manifest;
            }
        }

        return $manifests;
    }

    /**
     * @param string[] $expectedTestKeys
     *
     * @return Test[]
     */
    private function createExpectedTestCollection(array $expectedTestKeys): array
    {
        $expectedTests = [];
        foreach ($expectedTestKeys as $expectedTestKey) {
            $expectedTest = $this->tests[$expectedTestKey] ?? null;
            if ($expectedTest instanceof Test) {
                $expectedTests[] = $expectedTest;
            }
        }

        return $expectedTests;
    }
}
