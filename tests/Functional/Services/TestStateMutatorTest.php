<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Test;
use App\Entity\TestConfiguration;
use App\Event\TestFailedEvent;
use App\Services\TestStateMutator;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\TestTestFactory;
use Psr\EventDispatcher\EventDispatcherInterface;

class TestStateMutatorTest extends AbstractBaseFunctionalTest
{
    private TestStateMutator $mutator;
    private EventDispatcherInterface $eventDispatcher;
    private Test $test;

    protected function setUp(): void
    {
        parent::setUp();

        $mutator = self::$container->get(TestStateMutator::class);
        self::assertInstanceOf(TestStateMutator::class, $mutator);
        if ($mutator instanceof TestStateMutator) {
            $this->mutator = $mutator;
        }

        $testFactory = self::$container->get(TestTestFactory::class);
        self::assertInstanceOf(TestTestFactory::class, $testFactory);
        if ($testFactory instanceof TestTestFactory) {
            $this->test = $testFactory->create(
                TestConfiguration::create('chrome', 'http://example.com/callback'),
                '/app/source/Test/test.yml',
                '/app/tests/GeneratedTest.php',
                1
            );
        }

        $eventDispatcher = self::$container->get(EventDispatcherInterface::class);
        self::assertInstanceOf(EventDispatcherInterface::class, $eventDispatcher);
        if ($eventDispatcher instanceof EventDispatcherInterface) {
            $this->eventDispatcher = $eventDispatcher;
        }
    }

    public function testSetFailedFromTestFailedEvent()
    {
        $this->doTestFailedEventDrivenTest(function (TestFailedEvent $event) {
            $this->mutator->setFailedFromTestFailedEvent($event);
        });
    }

    public function testSubscribesToTestFailedEvent()
    {
        $this->doTestFailedEventDrivenTest(function (TestFailedEvent $event) {
            $this->eventDispatcher->dispatch($event);
        });
    }

    private function doTestFailedEventDrivenTest(callable $callable): void
    {
        self::assertNotSame(Test::STATE_FAILED, $this->test->getState());

        $event = new TestFailedEvent($this->test);

        $callable($event);

        self::assertSame(Test::STATE_FAILED, $this->test->getState());
    }
}
