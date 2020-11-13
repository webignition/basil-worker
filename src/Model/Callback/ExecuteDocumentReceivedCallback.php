<?php

declare(strict_types=1);

namespace App\Model\Callback;

use App\Entity\CallbackEntity;
use App\Entity\CallbackEntityInterface;
use webignition\YamlDocument\Document;

class ExecuteDocumentReceivedCallback extends AbstractCallbackEntityWrapper implements CallbackEntityWrapperInterface
{
    private Document $document;

    public function __construct(Document $document)
    {
        $this->document = $document;
        $documentData = $document->parse();
        $documentData = is_array($documentData) ? $documentData : [];

        parent::__construct(CallbackEntity::create(
            CallbackEntityInterface::TYPE_EXECUTE_DOCUMENT_RECEIVED,
            $documentData
        ));
    }

    public function getDocument(): Document
    {
        return $this->document;
    }
}
