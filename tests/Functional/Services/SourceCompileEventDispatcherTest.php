<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Event\SourceCompile\SourceCompileFailureEvent;
use App\Event\SourceCompile\SourceCompileSuccessEvent;
use App\Services\SourceCompileEventDispatcher;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\MockEventDispatcher;
use App\Tests\Model\ExpectedDispatchedEvent;
use App\Tests\Model\ExpectedDispatchedEventCollection;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\EventDispatcher\Event;
use webignition\BasilCompilerModels\ErrorOutputInterface;
use webignition\BasilCompilerModels\OutputInterface;
use webignition\BasilCompilerModels\SuiteManifest;
use webignition\ObjectReflector\ObjectReflector;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class SourceCompileEventDispatcherTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;
    use TestClassServicePropertyInjectorTrait;

    private SourceCompileEventDispatcher $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();

//        $dispatcher = self::$container->get(SourceCompileEventDispatcher::class);
//        if ($dispatcher instanceof SourceCompileEventDispatcher) {
//            $this->dispatcher = $dispatcher;
//        }
    }

    public function testDispatchEventNotDispatched()
    {
        $source = 'Test/test1.yml';
        $output = \Mockery::mock(OutputInterface::class);

        $this->dispatcher->dispatch($source, $output);

        $eventDispatcher = (new MockEventDispatcher())
            ->withoutDispatchCall()
            ->getMock();

        $this->setSourceCompileEventDispatcherEventDispatcher($eventDispatcher);

        $this->dispatcher->dispatch($source, $output);
    }

    /**
     * @dataProvider dispatchEventDispatchedDataProvider
     */
    public function testDispatchEventDispatched(
        string $source,
        OutputInterface $output,
        Event $expectedEvent
    ) {
        $eventDispatcher = (new MockEventDispatcher())
            ->withDispatchCalls(new ExpectedDispatchedEventCollection([
                new ExpectedDispatchedEvent($expectedEvent)
            ]))
            ->getMock();

        $this->setSourceCompileEventDispatcherEventDispatcher($eventDispatcher);

        $this->dispatcher->dispatch($source, $output);
    }

    public function dispatchEventDispatchedDataProvider(): array
    {
        $errorOutput = \Mockery::mock(ErrorOutputInterface::class);
        $successOutput = \Mockery::mock(SuiteManifest::class);

        return [
            'error output' => [
                'source' => 'Test/test1.yml',
                'output' => $errorOutput,
                'expectedEvent' => new SourceCompileFailureEvent('Test/test1.yml', $errorOutput),
            ],
            'success output' => [
                'source' => 'Test/test2.yml',
                'output' => $successOutput,
                'expectedEvent' => new SourceCompileSuccessEvent('Test/test2.yml', $successOutput),
            ],
        ];
    }

    private function setSourceCompileEventDispatcherEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        ObjectReflector::setProperty(
            $this->dispatcher,
            SourceCompileEventDispatcher::class,
            'dispatcher',
            $eventDispatcher
        );
    }
}
