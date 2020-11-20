<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageHandler;

use App\Entity\Job;
use App\Message\CompileSource;
use App\MessageHandler\CompileSourceHandler;
use App\Services\SourceCompileEventDispatcher;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\MockSuiteManifest;
use App\Tests\Mock\Services\MockCompiler;
use App\Tests\Mock\Services\MockSourceCompileEventDispatcher;
use App\Tests\Services\InvokableFactory\JobSetup;
use App\Tests\Services\InvokableFactory\JobSetupInvokableFactory;
use App\Tests\Services\InvokableHandler;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use webignition\BasilCompilerModels\ErrorOutputInterface;
use webignition\BasilCompilerModels\TestManifest;
use webignition\ObjectReflector\ObjectReflector;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class CompileSourceHandlerTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;
    use TestClassServicePropertyInjectorTrait;

    private CompileSourceHandler $handler;
    private InvokableHandler $invokableHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    public function testInvokeNoJob()
    {
        $eventDispatcher = (new MockSourceCompileEventDispatcher())
            ->withoutDispatchCall()
            ->getMock();

        ObjectReflector::setProperty($this->handler, CompileSourceHandler::class, 'eventDispatcher', $eventDispatcher);

        $handler = $this->handler;
        $handler(\Mockery::mock(CompileSource::class));
    }

    public function testInvokeJobInWrongState()
    {
        $this->invokableHandler->invoke(JobSetupInvokableFactory::setup(
            (new JobSetup())
                ->withState(Job::STATE_COMPILATION_AWAITING)
        ));

        $eventDispatcher = (new MockSourceCompileEventDispatcher())
            ->withoutDispatchCall()
            ->getMock();

        ObjectReflector::setProperty($this->handler, CompileSourceHandler::class, 'eventDispatcher', $eventDispatcher);

        $handler = $this->handler;
        $handler(\Mockery::mock(CompileSource::class));
    }

    public function testInvokeCompileSuccess()
    {
        $this->invokableHandler->invoke(JobSetupInvokableFactory::setup(
            (new JobSetup())
                ->withState(Job::STATE_COMPILATION_RUNNING)
        ));

        $source = 'Test/test1.yml';
        $compileSourceMessage = new CompileSource($source);

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

        $eventDispatcher = (new MockSourceCompileEventDispatcher())
            ->withDispatchCall($source, $suiteManifest)
            ->getMock();

        $this->setCompileSourceHandlerEventDispatcher($eventDispatcher);

        $handler = $this->handler;
        $handler($compileSourceMessage);
    }

    public function testInvokeCompileFailure()
    {
        $this->invokableHandler->invoke(JobSetupInvokableFactory::setup(
            (new JobSetup())
                ->withState(Job::STATE_COMPILATION_RUNNING)
        ));

        $source = 'Test/test1.yml';
        $compileSourceMessage = new CompileSource($source);
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

        $eventDispatcher = (new MockSourceCompileEventDispatcher())
            ->withDispatchCall($source, $errorOutput)
            ->getMock();

        $this->setCompileSourceHandlerEventDispatcher($eventDispatcher);

        $handler = $this->handler;
        $handler($compileSourceMessage);
    }

    private function setCompileSourceHandlerEventDispatcher(SourceCompileEventDispatcher $eventDispatcher): void
    {
        ObjectReflector::setProperty($this->handler, CompileSourceHandler::class, 'eventDispatcher', $eventDispatcher);
    }
}
