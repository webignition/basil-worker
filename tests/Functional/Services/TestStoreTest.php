<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Test;
use App\Services\ManifestStore;
use App\Services\TestStore;
use App\Tests\Functional\AbstractBaseFunctionalTest;
use Doctrine\ORM\EntityManagerInterface;
use webignition\BasilCompilerModels\TestManifest;
use webignition\BasilModels\Test\Configuration;

class TestStoreTest extends AbstractBaseFunctionalTest
{
    private TestStore $testStore;
    private ManifestStore $manifestStore;

    protected function setUp(): void
    {
        parent::setUp();

        $store = self::$container->get(TestStore::class);
        self::assertInstanceOf(TestStore::class, $store);

        if ($store instanceof TestStore) {
            $this->testStore = $store;
        }

        $manifestStore = self::$container->get(ManifestStore::class);
        if ($manifestStore instanceof ManifestStore) {
            $this->manifestStore = $manifestStore;
        }
    }

    public function testCreate()
    {
        $tests = $this->createTestSet();

        foreach ($tests as $testIndex => $test) {
            $expectedPosition = $testIndex + 1;
            self::assertSame($expectedPosition, $test->getPosition());
        }
    }

    public function testCreateFromTestManifest()
    {
        $manifest = new TestManifest(
            new Configuration('chrome', 'http://example.com'),
            'Tests/test.yml',
            '/app/tests/GeneratedTest.php',
            2
        );

        $test = $this->testStore->createFromTestManifest($manifest, '');

        $this->assertTestMatchesManifest($manifest, $test);
        self::assertSame(1, $test->getPosition());
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
            $test = $this->testStore->find($testId);

            self::assertInstanceOf(Test::class, $test);
            self::assertNotNull($test->getConfiguration());
            self::assertNotNull($test->getTarget());
            self::assertNotNull($test->getStepCount());
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

        $manifest = new TestManifest(
            new Configuration('chrome', 'http://example.com'),
            'Test/test1.yml',
            'generated/GeneratedTest1.php',
            3
        );

        $manifestPath = $this->manifestStore->store($manifest);

        $test1 = Test::create('source', $manifest, $manifestPath, 2);
        $entityManager->persist($test1);
        $entityManager->flush();

        $test2 = Test::create('source', $manifest, $manifestPath, 1);
        $entityManager->persist($test2);
        $entityManager->flush();

        $test3 = Test::create('source', $manifest, $manifestPath, 3);
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

    public function testFindNextAwaiting()
    {
        $tests = $this->createTestSet();

        foreach ($tests as $test) {
            self::assertEquals($test, $this->testStore->findNextAwaiting());
            $test->setState(Test::STATE_RUNNING);
            $this->testStore->store($test);
        }
    }

    /**
     * @return Test[]
     */
    private function createTestSet(): array
    {
        $manifest1 = new TestManifest(
            new Configuration('chrome', 'http://example.com'),
            'Test/test1.yml',
            'generated/GeneratedTest1.php',
            3
        );

        $manifest2 = new TestManifest(
            new Configuration('chrome', 'http://example.com'),
            'Test/test2.yml',
            'generated/GeneratedTest2.php',
            2
        );

        $manifest3 = new TestManifest(
            new Configuration('chrome', 'http://example.com'),
            'Test/test3.yml',
            'generated/GeneratedTest3.php',
            1
        );

        $manifestPath1 = $this->manifestStore->store($manifest1);
        $manifestPath2 = $this->manifestStore->store($manifest2);
        $manifestPath3 = $this->manifestStore->store($manifest3);

        return [
            $this->testStore->create('Test/test1.yml', $manifest1, $manifestPath1),
            $this->testStore->create('Test/test2.yml', $manifest2, $manifestPath2),
            $this->testStore->create('Test/test3.yml', $manifest3, $manifestPath3),
        ];
    }

    private function assertTestMatchesManifest(TestManifest $manifest, Test $test): void
    {
        self::assertInstanceOf(Test::class, $test);
        self::assertIsInt($test->getId());
        self::assertSame($manifest->getSource(), $test->getSource());
        self::assertSame($manifest->getTarget(), $test->getTarget());
        self::assertSame($manifest->getStepCount(), $test->getStepCount());
    }
}
