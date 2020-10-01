<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageHandler;

use App\Entity\Job;
use App\Event\SourceCompileFailureEvent;
use App\Event\SourceCompileSuccessEvent;
use App\Message\CompileSource;
use App\MessageHandler\CompileSourceHandler;
use App\Services\JobStore;
use App\Tests\Functional\AbstractBaseFunctionalTest;
use App\Tests\Mock\Entity\MockJob;
use App\Tests\Mock\MockSuiteManifest;
use App\Tests\Mock\Services\MockCompiler;
use App\Tests\Mock\Services\MockJobStore;
use App\Tests\Services\SourceCompileFailureEventSubscriber;
use App\Tests\Services\SourceCompileSuccessEventSubscriber;
use webignition\BasilCompilerModels\ErrorOutputInterface;
use webignition\BasilCompilerModels\TestManifest;
use webignition\ObjectReflector\ObjectReflector;

class CompileSourceHandlerTest extends AbstractBaseFunctionalTest
{
    private CompileSourceHandler $handler;
    private SourceCompileSuccessEventSubscriber $successEventSubscriber;
    private SourceCompileFailureEventSubscriber $failureEventSubscriber;
    private Job $job;

    protected function setUp(): void
    {
        parent::setUp();

        $handler = self::$container->get(CompileSourceHandler::class);
        if ($handler instanceof CompileSourceHandler) {
            $this->handler = $handler;
        }

        $jobStore = self::$container->get(JobStore::class);
        if ($jobStore instanceof JobStore) {
            $this->job = $jobStore->create('label content', 'http://example.com/callback');
            $this->job->setState(Job::STATE_COMPILATION_RUNNING);
            $jobStore->store();
        }

        $successEventSubscriber = self::$container->get(SourceCompileSuccessEventSubscriber::class);
        if ($successEventSubscriber instanceof SourceCompileSuccessEventSubscriber) {
            $this->successEventSubscriber = $successEventSubscriber;
        }

        $failureEventSubscriber = self::$container->get(SourceCompileFailureEventSubscriber::class);
        if ($failureEventSubscriber instanceof SourceCompileFailureEventSubscriber) {
            $this->failureEventSubscriber = $failureEventSubscriber;
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

        $handler = $this->handler;
        $handler(\Mockery::mock(CompileSource::class));

        self::assertNull($this->successEventSubscriber->getEvent());
        self::assertNull($this->failureEventSubscriber->getEvent());
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

        $handler = $this->handler;
        $handler($compileSourceMessage);

        self::assertEquals(
            new SourceCompileSuccessEvent($compileSourceMessage->getSource(), $testManifests),
            $this->successEventSubscriber->getEvent()
        );
        self::assertNull($this->failureEventSubscriber->getEvent());
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

        $handler = $this->handler;
        $handler($compileSourceMessage);

        self::assertNull($this->successEventSubscriber->getEvent());
        self::assertEquals(
            new SourceCompileFailureEvent($compileSourceMessage->getSource(), $errorOutput),
            $this->failureEventSubscriber->getEvent()
        );

        self::assertSame(Job::STATE_COMPILATION_FAILED, $this->job->getState());
    }
}
