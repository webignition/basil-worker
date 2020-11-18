<?php

declare(strict_types=1);

namespace App\Entity\Callback;

use webignition\YamlDocument\Document;

class ExecuteDocumentReceivedCallback extends AbstractCallbackWrapper
{
    private Document $document;

    public function __construct(Document $document)
    {
        $this->document = $document;
        $documentData = $document->parse();
        $documentData = is_array($documentData) ? $documentData : [];

        parent::__construct(CallbackEntity::create(
            CallbackInterface::TYPE_EXECUTE_DOCUMENT_RECEIVED,
            $documentData
        ));
    }

    public function getDocument(): Document
    {
        return $this->document;
    }
}
