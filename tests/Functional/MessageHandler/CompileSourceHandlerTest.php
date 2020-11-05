<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageHandler;

use App\Entity\Job;
use App\Event\SourceCompileFailureEvent;
use App\Event\SourceCompileSuccessEvent;
use App\Message\CompileSource;
use App\MessageHandler\CompileSourceHandler;
use App\Services\JobStore;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\Entity\MockJob;
use App\Tests\Mock\MockEventDispatcher;
use App\Tests\Mock\MockSuiteManifest;
use App\Tests\Mock\Services\MockCompiler;
use App\Tests\Mock\Services\MockJobStore;
use App\Tests\Model\ExpectedDispatchedEvent;
use App\Tests\Model\ExpectedDispatchedEventCollection;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use webignition\BasilCompilerModels\ErrorOutputInterface;
use webignition\BasilCompilerModels\TestManifest;
use webignition\ObjectReflector\ObjectReflector;

class CompileSourceHandlerTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;

    private CompileSourceHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $handler = self::$container->get(CompileSourceHandler::class);
        if ($handler instanceof CompileSourceHandler) {
            $this->handler = $handler;
        }

        $jobStore = self::$container->get(JobStore::class);
        if ($jobStore instanceof JobStore) {
            $job = $jobStore->create('label content', 'http://example.com/callback');
            $job->setState(Job::STATE_COMPILATION_RUNNING);
            $jobStore->store($job);
        }
    }

    /**
     * @dataProvider invokeNoCompilationDataProvider
     */
    public function testInvokeNoCompilation(JobStore $jobStore)
    {
        ObjectReflector::setProperty(
            $this->handler,
            CompileSourceHandler::class,
            'jobStore',
            $jobStore
        );

        $eventDispatcher = (new MockEventDispatcher())
            ->withoutDispatchCall()
            ->getMock();

        ObjectReflector::setProperty($this->handler, CompileSourceHandler::class, 'eventDispatcher', $eventDispatcher);

        $handler = $this->handler;
        $handler(\Mockery::mock(CompileSource::class));
    }

    public function invokeNoCompilationDataProvider(): array
    {
        $jobInWrongState = (new MockJob())
            ->withGetStateCall(Job::STATE_COMPILATION_AWAITING)
            ->getMock();

        return [
            'no job' => [
                'jobStore' => (new MockJobStore())
                    ->withHasJobCall(false)
                    ->getMock(),
            ],
            'job in wrong state' => [
                'jobStore' => (new MockJobStore())
                    ->withHasJobCall(true)
                    ->withGetJobCall($jobInWrongState)
                    ->getMock(),
            ],
        ];
    }

    public function testInvokeCompileSuccess()
    {
        $compileSourceMessage = new CompileSource('Test/test1.yml');

        $testManifests = [
            \Mockery::mock(TestManifest::class),
            \Mockery::mock(TestManifest::class),
        ];

        $suiteManifest = (new MockSuiteManifest())
            ->withGetTestManifestsCall($testManifests)
            ->getMock();

        $compiler = (new MockCompiler())
            ->withCompileCall(
                $compileSourceMessage->getSource(),
                $suiteManifest
            )
            ->getMock();

        ObjectReflector::setProperty(
            $this->handler,
            CompileSourceHandler::class,
            'compiler',
            $compiler
        );

        $eventDispatcher = (new MockEventDispatcher())
            ->withDispatchCalls(new ExpectedDispatchedEventCollection([
                new ExpectedDispatchedEvent(
                    new SourceCompileSuccessEvent('Test/test1.yml', $suiteManifest)
                )
            ]))
            ->getMock();

        ObjectReflector::setProperty($this->handler, CompileSourceHandler::class, 'eventDispatcher', $eventDispatcher);

        $handler = $this->handler;
        $handler($compileSourceMessage);
    }

    public function testInvokeCompileFailure()
    {
        $compileSourceMessage = new CompileSource('Test/test1.yml');
        $errorOutput = \Mockery::mock(ErrorOutputInterface::class);

        $compiler = (new MockCompiler())
            ->withCompileCall(
                $compileSourceMessage->getSource(),
                $errorOutput
            )
            ->getMock();

        ObjectReflector::setProperty(
            $this->handler,
            CompileSourceHandler::class,
            'compiler',
            $compiler
        );

        $eventDispatcher = (new MockEventDispatcher())
            ->withDispatchCalls(new ExpectedDispatchedEventCollection([
                new ExpectedDispatchedEvent(
                    new SourceCompileFailureEvent('Test/test1.yml', $errorOutput)
                )
            ]))
            ->getMock();

        ObjectReflector::setProperty($this->handler, CompileSourceHandler::class, 'eventDispatcher', $eventDispatcher);

        $handler = $this->handler;
        $handler($compileSourceMessage);
    }
}
