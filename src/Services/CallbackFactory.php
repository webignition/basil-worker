<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\CallbackEntity;
use webignition\BasilCompilerModels\ErrorOutputInterface;
use webignition\YamlDocument\Document;

class CallbackFactory
{
    private CallbackStore $callbackStore;

    public function __construct(CallbackStore $callbackStore)
    {
        $this->callbackStore = $callbackStore;
    }

    public function createForCompileFailure(ErrorOutputInterface $errorOutput): CallbackEntity
    {
        return $this->create(CallbackEntity::TYPE_COMPILE_FAILURE, $errorOutput->getData());
    }

    public function createForExecuteDocumentReceived(Document $document): CallbackEntity
    {
        $documentData = $document->parse();
        $documentData = is_array($documentData) ? $documentData : [];

        return $this->create(CallbackEntity::TYPE_EXECUTE_DOCUMENT_RECEIVED, $documentData);
    }

    /**
     * @param CallbackEntity::TYPE_* $type
     * @param array<mixed> $payload
     *
     * @return CallbackEntity
     */
    private function create(string $type, array $payload): CallbackEntity
    {
        return $this->callbackStore->store(
            CallbackEntity::create($type, $payload)
        );
    }
}
