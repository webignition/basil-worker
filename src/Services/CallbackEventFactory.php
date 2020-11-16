<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Callback\CompileFailureCallback;
use App\Entity\Callback\ExecuteDocumentReceivedCallback;
use App\Entity\Test;
use App\Event\SourceCompile\SourceCompileFailureEvent;
use App\Event\TestExecuteDocumentReceivedEvent;
use webignition\BasilCompilerModels\ErrorOutputInterface;
use webignition\YamlDocument\Document;

class CallbackEventFactory
{
    private CallbackStore $callbackStore;

    public function __construct(CallbackStore $callbackStore)
    {
        $this->callbackStore = $callbackStore;
    }

    public function createSourceCompileFailureEvent(
        string $source,
        ErrorOutputInterface $errorOutput
    ): SourceCompileFailureEvent {
        $callback = new CompileFailureCallback($errorOutput);
        $this->callbackStore->store($callback);

        return new SourceCompileFailureEvent($source, $errorOutput, $callback);
    }

    public function createTestExecuteDocumentReceivedEvent(
        Test $test,
        Document $document
    ): TestExecuteDocumentReceivedEvent {
        $callback = new ExecuteDocumentReceivedCallback($document);
        $this->callbackStore->store($callback);

        return new TestExecuteDocumentReceivedEvent($test, $document, $callback);
    }
}
