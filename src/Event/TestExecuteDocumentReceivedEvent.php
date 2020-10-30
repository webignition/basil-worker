<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Test;
use webignition\YamlDocument\Document;

class TestExecuteDocumentReceivedEvent extends AbstractTestEvent
{
    public const NAME = 'worker.test.execute.document-received';

    private Document $document;

    public function __construct(Test $test, Document $document)
    {
        parent::__construct($test);

        $this->document = $document;
    }

    public function getDocument(): Document
    {
        return $this->document;
    }
}
