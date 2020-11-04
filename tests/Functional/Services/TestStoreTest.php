<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Test;
use App\Entity\TestConfiguration;
use App\Repository\TestRepository;
use App\Services\TestStore;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\TestTestFactory;

class TestStoreTest extends AbstractBaseFunctionalTest
{
    private TestStore $testStore;
    private TestTestFactory $testFactory;
    private TestRepository $testRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $store = self::$container->get(TestStore::class);
        self::assertInstanceOf(TestStore::class, $store);
        if ($store instanceof TestStore) {
            $this->testStore = $store;
        }

        $testFactory = self::$container->get(TestTestFactory::class);
        self::assertInstanceOf(TestTestFactory::class, $testFactory);
        if ($testFactory instanceof TestTestFactory) {
            $this->testFactory = $testFactory;
        }

        $testRepository = self::$container->get(TestRepository::class);
        self::assertInstanceOf(TestRepository::class, $testRepository);
        if ($testRepository instanceof TestRepository) {
            $this->testRepository = $testRepository;
        }
    }

    public function testStore()
    {
        $test = $this->testFactory->createFoo(
            TestConfiguration::create('chrome', 'http://example.com'),
            '/app/source/Test/test.yml',
            '/app/tests/GeneratedTest.php',
            1
        );

        self::assertSame(Test::STATE_AWAITING, $test->getState());

        $test->setState(Test::STATE_RUNNING);
        $this->testStore->store($test);

        $retrievedTest = $this->testRepository->find($test->getId());
        self::assertEquals($test, $retrievedTest);
    }
}
