<?php

declare(strict_types=1);

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;
use webignition\YamlDocument\Document;

class TestExecuteDocumentReceivedEvent extends Event
{
    public const NAME = 'worker.test.execute.document-received';

    private Document $document;

    public function __construct(Document $document)
    {
        $this->document = $document;
    }

    public function getDocument(): Document
    {
        return $this->document;
    }
}
