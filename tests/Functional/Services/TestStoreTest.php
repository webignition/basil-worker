<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Test;
use App\Entity\TestConfiguration;
use App\Services\TestConfigurationStore;
use App\Services\TestStore;
use App\Tests\AbstractBaseFunctionalTest;
use Doctrine\ORM\EntityManagerInterface;
use webignition\BasilCompilerModels\TestManifest;
use webignition\BasilModels\Test\Configuration;

class TestStoreTest extends AbstractBaseFunctionalTest
{
    private TestStore $testStore;

    protected function setUp(): void
    {
        parent::setUp();

        $store = self::$container->get(TestStore::class);
        self::assertInstanceOf(TestStore::class, $store);

        if ($store instanceof TestStore) {
            $this->testStore = $store;
        }
    }

    public function testFind()
    {
        $tests = $this->createTestSet();

        $testIds = [];
        foreach ($tests as $test) {
            $testId = $test->getId();

            if (is_int($testId)) {
                $testIds[] = $testId;
            }
        }

        foreach ($testIds as $testId) {
            self::assertInstanceOf(Test::class, $this->testStore->find($testId));
        }

        self::assertNull($this->testStore->find(0));

        $minTestId = min($testIds);
        self::assertNull($this->testStore->find($minTestId - 1));

        $maxTestId = max($testIds);
        self::assertNull($this->testStore->find($maxTestId + 1));
    }

    public function testFindAllEmpty()
    {
        self::assertSame([], $this->testStore->findAll());
    }

    public function testFindAllNonEmpty()
    {
        $tests = $this->createTestSet();

        self::assertSame($tests, $this->testStore->findAll());
    }

    public function testFindAllOrdersByPosition()
    {
        $entityManager = self::$container->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        $testConfigurationStore = self::$container->get(TestConfigurationStore::class);
        self::assertInstanceOf(TestConfigurationStore::class, $testConfigurationStore);

        $testConfiguration = $testConfigurationStore->find('chrome', 'http:/example.com');

        $test1 = Test::create($testConfiguration, 'source', 'target', 3, 2);
        $entityManager->persist($test1);
        $entityManager->flush();

        $test2 = Test::create($testConfiguration, 'source', 'target', 3, 1);
        $entityManager->persist($test2);
        $entityManager->flush();

        $test3 = Test::create($testConfiguration, 'source', 'target', 3, 3);
        $entityManager->persist($test3);
        $entityManager->flush();

        self::assertEquals(
            [
                $test2,
                $test1,
                $test3,
            ],
            $this->testStore->findAll()
        );
    }

    public function testCreate()
    {
        $tests = $this->createTestSet();

        foreach ($tests as $testIndex => $test) {
            $expectedPosition = $testIndex + 1;
            self::assertSame($expectedPosition, $test->getPosition());
        }
    }

    public function testFindNextAwaiting()
    {
        $tests = $this->createTestSet();

        foreach ($tests as $test) {
            self::assertEquals($test, $this->testStore->findNextAwaiting());
            $test->setState(Test::STATE_RUNNING);
            $this->testStore->store($test);
        }
    }

    public function testCreateFromTestManifest()
    {
        $testConfigurationStore = self::$container->get(TestConfigurationStore::class);
        self::assertInstanceOf(TestConfigurationStore::class, $testConfigurationStore);

        $manifest = new TestManifest(
            new Configuration('chrome', 'http://example.com'),
            'Tests/test.yml',
            '/app/tests/GeneratedTest.php',
            2
        );

        $test = $this->testStore->createFromTestManifest($manifest);

        $this->assertTestMatchesManifest($manifest, $test);
        self::assertSame(1, $test->getPosition());
    }

    public function testCreateFromTestManifests()
    {
        $manifest1 = new TestManifest(
            new Configuration('chrome', 'http://example.com'),
            'Tests/test.yml',
            '/app/tests/GeneratedChromeTest.php',
            2
        );

        $manifest2 = new TestManifest(
            new Configuration('firefox', 'http://example.com'),
            'Tests/test.yml',
            '/app/tests/GeneratedFirefoxTest.php',
            2
        );

        $manifests = [
            $manifest1,
            $manifest2,
        ];

        $tests = $this->testStore->createFromTestManifests($manifests);

        foreach ($tests as $testIndex => $test) {
            $this->assertTestMatchesManifest($manifests[$testIndex], $test);
            self::assertSame($testIndex + 1, $test->getPosition());
        }
    }

    /**
     * @return Test[]
     */
    private function createTestSet(): array
    {
        return [
            $this->testStore->create(
                TestConfiguration::create('chrome', 'http://example.com'),
                'Test/test1.yml',
                'generated/GeneratedTest1.php',
                3
            ),
            $this->testStore->create(
                TestConfiguration::create('chrome', 'http://example.com'),
                'Test/test2.yml',
                'generated/GeneratedTest2.php',
                2
            ),
            $this->testStore->create(
                TestConfiguration::create('firefox', 'http://example.com'),
                'Test/test2.yml',
                'generated/GeneratedTest3.php',
                2
            ),
        ];
    }

    private function assertTestMatchesManifest(TestManifest $manifest, Test $test): void
    {
        $testConfigurationStore = self::$container->get(TestConfigurationStore::class);
        self::assertInstanceOf(TestConfigurationStore::class, $testConfigurationStore);

        $manifestConfiguration = $manifest->getConfiguration();

        self::assertInstanceOf(Test::class, $test);
        self::assertIsInt($test->getId());
        self::assertSame(
            $testConfigurationStore->find($manifestConfiguration->getBrowser(), $manifestConfiguration->getUrl()),
            $test->getConfiguration()
        );
        self::assertSame($manifest->getSource(), $test->getSource());
        self::assertSame($manifest->getTarget(), $test->getTarget());
        self::assertSame($manifest->getStepCount(), $test->getStepCount());
    }
}
