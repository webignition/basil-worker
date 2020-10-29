<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageHandler;

use App\Entity\Job;
use App\Entity\Test;
use App\Entity\TestConfiguration;
use App\Message\ExecuteTest;
use App\MessageHandler\ExecuteTestHandler;
use App\Services\JobStore;
use App\Services\TestStore;
use App\Tests\Functional\AbstractBaseFunctionalTest;
use App\Tests\Mock\Services\MockTestExecutor;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use webignition\ObjectReflector\ObjectReflector;

class ExecuteTestHandlerTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;

    private ExecuteTestHandler $handler;
    private Job $job;
    private Test $test;

    protected function setUp(): void
    {
        parent::setUp();

        $handler = self::$container->get(ExecuteTestHandler::class);
        self::assertInstanceOf(ExecuteTestHandler::class, $handler);

        if ($handler instanceof ExecuteTestHandler) {
            $this->handler = $handler;
        }

        $jobStore = self::$container->get(JobStore::class);
        self::assertInstanceOf(JobStore::class, $jobStore);
        $this->job = $jobStore->create('label content', 'http://example.com/callback');
        $this->job->setState(Job::STATE_EXECUTION_AWAITING);
        $jobStore->store($this->job);

        $testStore = self::$container->get(TestStore::class);
        self::assertInstanceOf(TestStore::class, $testStore);

        $this->test = $testStore->create(
            TestConfiguration::create('chrome', 'http://example.com'),
            '/tests/test1.yml',
            '/generated/GeneratedTest.php',
            1
        );
    }

    public function testInvokeExecuteSuccess()
    {
        self::assertSame(Job::STATE_EXECUTION_AWAITING, $this->job->getState());
        self::assertSame(Test::STATE_AWAITING, $this->test->getState());

        $testExecutor = (new MockTestExecutor())
            ->withExecuteCall($this->test)
            ->getMock();

        $executeTestMessage = new ExecuteTest((int) $this->test->getId());

        ObjectReflector::setProperty($this->handler, ExecuteTestHandler::class, 'testExecutor', $testExecutor);

        $handler = $this->handler;
        $handler($executeTestMessage);

        self::assertSame(Job::STATE_EXECUTION_RUNNING, $this->job->getState());
        self::assertSame(Test::STATE_COMPLETE, $this->test->getState());
    }
}
