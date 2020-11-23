<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageHandler;

use App\Entity\Test;
use App\Message\ExecuteTest;
use App\MessageHandler\ExecuteTestHandler;
use App\Model\JobState;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\Services\MockTestExecutor;
use App\Tests\Services\InvokableFactory\JobSetup;
use App\Tests\Services\InvokableFactory\JobSetupInvokableFactory;
use App\Tests\Services\InvokableFactory\JobStateGetterFactory;
use App\Tests\Services\InvokableFactory\TestSetup;
use App\Tests\Services\InvokableFactory\TestSetupInvokableFactory;
use App\Tests\Services\InvokableHandler;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use webignition\ObjectReflector\ObjectReflector;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class ExecuteTestHandlerTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;
    use TestClassServicePropertyInjectorTrait;

    private ExecuteTestHandler $handler;
    private InvokableHandler $invokableHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    public function testInvokeExecuteSuccess()
    {
        $this->invokableHandler->invoke(JobSetupInvokableFactory::setup(
            (new JobSetup())
                ->withSources([
                    'Test/test.yml',
                ])
        ));

        $tests = $this->invokableHandler->invoke(TestSetupInvokableFactory::setupCollection([
            new TestSetup(),
        ]));

        $test = $tests[0];

        $jobState = $this->invokableHandler->invoke(JobStateGetterFactory::get());
        self::assertSame(JobState::STATE_EXECUTION_AWAITING, (string) $jobState);
        self::assertSame(Test::STATE_AWAITING, $test->getState());

        $testExecutor = (new MockTestExecutor())
            ->withExecuteCall($test)
            ->getMock();

        $executeTestMessage = new ExecuteTest((int) $test->getId());

        ObjectReflector::setProperty($this->handler, ExecuteTestHandler::class, 'testExecutor', $testExecutor);

        $handler = $this->handler;
        $handler($executeTestMessage);

        $jobState = $this->invokableHandler->invoke(JobStateGetterFactory::get());
        self::assertSame(JobState::STATE_EXECUTION_COMPLETE, (string) $jobState);
        self::assertSame(Test::STATE_COMPLETE, $test->getState());
    }
}
