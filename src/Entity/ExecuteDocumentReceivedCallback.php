<?php

declare(strict_types=1);

namespace App\Entity;

use webignition\YamlDocument\Document;

class ExecuteDocumentReceivedCallback extends CallbackEntity
{
    public static function create(Document $document): CallbackEntityInterface
    {
        $documentData = $document->parse();
        $documentData = is_array($documentData) ? $documentData : [];

        return parent::createForTypeAndPayload(self::TYPE_EXECUTE_DOCUMENT_RECEIVED, $documentData);
    }
}
