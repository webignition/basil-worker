<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Event\SourceCompile\FooSourceCompileFailureEvent;
use App\Services\CallbackEventFactory;
use webignition\BasilCompilerModels\ErrorOutputInterface;

class TestCallbackEventFactory
{
    private CallbackEventFactory $callbackEventFactory;

    public function __construct(CallbackEventFactory $callbackEventFactory)
    {
        $this->callbackEventFactory = $callbackEventFactory;
    }

    public function createSourceCompileFailureEvent(
        string $source,
        array $errorOutputData
    ): FooSourceCompileFailureEvent {
        $errorOutput = \Mockery::mock(ErrorOutputInterface::class);
        $errorOutput
            ->shouldReceive('getData')
            ->andReturn($errorOutputData);

        return $this->callbackEventFactory->createSourceCompileFailureEvent($source, $errorOutput);
    }

    public function createEmptyPayloadSourceCompileFailureEvent(): FooSourceCompileFailureEvent
    {
        return $this->createSourceCompileFailureEvent('/app/source/Test/test.yml', []);
    }
}
