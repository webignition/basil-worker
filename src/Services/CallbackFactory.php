<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Callback\CompileFailureCallback;
use App\Entity\Callback\ExecuteDocumentReceivedCallback;
use webignition\BasilCompilerModels\ErrorOutputInterface;
use webignition\YamlDocument\Document;

class CallbackFactory
{
    private CallbackStore $callbackStore;

    public function __construct(CallbackStore $callbackStore)
    {
        $this->callbackStore = $callbackStore;
    }

    public function createForCompileFailure(ErrorOutputInterface $errorOutput): CompileFailureCallback
    {
        $callback = new CompileFailureCallback($errorOutput);

        $this->callbackStore->store($callback->getEntity());

        return $callback;
    }

    public function createForExecuteDocumentReceived(Document $document): ExecuteDocumentReceivedCallback
    {
        $callback = new ExecuteDocumentReceivedCallback($document);

        $this->callbackStore->store($callback->getEntity());

        return $callback;
    }
}
