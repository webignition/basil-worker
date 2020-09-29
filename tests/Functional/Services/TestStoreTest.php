<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Test;
use App\Entity\TestConfiguration;
use App\Services\TestStore;
use App\Tests\Functional\AbstractBaseFunctionalTest;

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

    public function testFindAllEmpty()
    {
        self::assertSame([], $this->testStore->findAll());
    }

    public function testFindAllNonEmpty()
    {
        $tests = $this->createTestSet();

        self::assertSame($tests, $this->testStore->findAll());
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
}
