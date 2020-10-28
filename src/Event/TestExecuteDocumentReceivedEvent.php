<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Test;
use Symfony\Contracts\EventDispatcher\Event;
use webignition\YamlDocument\Document;

class TestExecuteDocumentReceivedEvent extends Event
{
    public const NAME = 'worker.test.execute.document-received';

    private Test $test;
    private Document $document;

    public function __construct(Test $test, Document $document)
    {
        $this->test = $test;
        $this->document = $document;
    }

    public function getTest(): Test
    {
        return $this->test;
    }

    public function getDocument(): Document
    {
        return $this->document;
    }
}
