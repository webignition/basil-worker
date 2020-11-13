<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\CallbackEntityInterface;
use App\Entity\CompileFailureCallback;
use App\Entity\ExecuteDocumentReceivedCallback;
use webignition\BasilCompilerModels\ErrorOutputInterface;
use webignition\YamlDocument\Document;

class CallbackFactory
{
    private CallbackStore $callbackStore;

    public function __construct(CallbackStore $callbackStore)
    {
        $this->callbackStore = $callbackStore;
    }

    public function createForCompileFailure(ErrorOutputInterface $errorOutput): CallbackEntityInterface
    {
        $callback = CompileFailureCallback::create($errorOutput);

        return $this->callbackStore->store($callback);
    }

    public function createForExecuteDocumentReceived(Document $document): CallbackEntityInterface
    {
        $callback = ExecuteDocumentReceivedCallback::create($document);

        return $this->callbackStore->store($callback);
    }
}
