<?php

declare(strict_types=1);

namespace App\Model\Callback;

use webignition\YamlDocument\Document;

class ExecuteDocumentReceived extends AbstractCallback
{
    public const TYPE = 'execute-document-received';

    private Document $document;

    public function __construct(Document $document)
    {
        $this->document = $document;
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    /**
     * @return array<mixed>
     */
    public function getData(): array
    {
        $data = $this->document->parse();

        return is_array($data) ? $data : [];
    }
}
