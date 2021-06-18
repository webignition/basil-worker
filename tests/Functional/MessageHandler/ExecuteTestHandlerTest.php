<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageHandler;

use App\Event\TestPassedEvent;
use App\Event\TestStartedEvent;
use App\Message\ExecuteTestMessage;
use App\MessageHandler\ExecuteTestHandler;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\MockEventDispatcher;
use App\Tests\Mock\Services\MockTestExecutor;
use App\Tests\Model\EnvironmentSetup;
use App\Tests\Model\ExpectedDispatchedEvent;
use App\Tests\Model\ExpectedDispatchedEventCollection;
use App\Tests\Model\JobSetup;
use App\Tests\Model\TestSetup;
use App\Tests\Services\EnvironmentFactory;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use webignition\BasilWorker\PersistenceBundle\Entity\Job;
use webignition\BasilWorker\PersistenceBundle\Entity\Test;
use webignition\BasilWorker\PersistenceBundle\Services\Store\JobStore;
use App\Services\ExecutionState;
use webignition\ObjectReflector\ObjectReflector;

class ExecuteTestHandlerTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;

    private ExecuteTestHandler $handler;
    private EnvironmentFactory $environmentFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $executeTestHandler = self::$container->get(ExecuteTestHandler::class);
        \assert($executeTestHandler instanceof ExecuteTestHandler);
        $this->handler = $executeTestHandler;

        $environmentFactory = self::$container->get(EnvironmentFactory::class);
        \assert($environmentFactory instanceof EnvironmentFactory);
        $this->environmentFactory = $environmentFactory;
    }

    public function testInvokeExecuteSuccess(): void
    {
        $environmentSetup = (new EnvironmentSetup())
            ->withJobSetup(new JobSetup())
            ->withTestSetups([
                new TestSetup(),
            ])
        ;

        $environment = $this->environmentFactory->create($environmentSetup);

        $tests = $environment->getTests();
        $test = $tests[0];

        $jobStore = self::$container->get(JobStore::class);
        \assert($jobStore instanceof JobStore);

        $job = $jobStore->get();
        self::assertInstanceOf(Job::class, $job);
        self::assertFalse($job->hasStarted());

        $executionState = self::$container->get(ExecutionState::class);
        \assert($executionState instanceof ExecutionState);

        self::assertSame(ExecutionState::STATE_AWAITING, (string) $executionState);
        self::assertSame(Test::STATE_AWAITING, $test->getState());

        $testExecutor = (new MockTestExecutor())
            ->withExecuteCall($test)
            ->getMock()
        ;

        ObjectReflector::setProperty($this->handler, ExecuteTestHandler::class, 'testExecutor', $testExecutor);

        $eventDispatcher = (new MockEventDispatcher())
            ->withDispatchCalls(new ExpectedDispatchedEventCollection([
                new ExpectedDispatchedEvent(
                    function (TestStartedEvent $actualEvent) use ($test) {
                        self::assertSame($test, $actualEvent->getTest());

                        return true;
                    },
                ),
                new ExpectedDispatchedEvent(
                    function (TestPassedEvent $actualEvent) use ($test) {
                        self::assertSame($test, $actualEvent->getTest());

                        return true;
                    },
                ),
            ]))
            ->getMock()
        ;

        ObjectReflector::setProperty($this->handler, ExecuteTestHandler::class, 'eventDispatcher', $eventDispatcher);

        $handler = $this->handler;

        $executeTestMessage = new ExecuteTestMessage((int) $test->getId());
        $handler($executeTestMessage);

        self::assertTrue($job->hasStarted());

        self::assertSame(ExecutionState::STATE_COMPLETE, (string) $executionState);
        self::assertSame(Test::STATE_COMPLETE, $test->getState());
    }
}
