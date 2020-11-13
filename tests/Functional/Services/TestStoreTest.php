<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Test;
use App\Entity\TestConfiguration;
use App\Repository\TestRepository;
use App\Services\TestStore;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\TestTestFactory;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class TestStoreTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private TestStore $testStore;
    private TestTestFactory $testFactory;
    private TestRepository $testRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    public function testStore()
    {
        $test = $this->testFactory->create(
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
