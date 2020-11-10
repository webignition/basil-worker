<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Test;
use App\Model\Callback\CallbackInterface;
use App\Model\Callback\ExecuteDocumentReceived;
use webignition\YamlDocument\Document;

class TestExecuteDocumentReceivedEvent extends AbstractTestEvent implements CallbackEventInterface
{
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

    public function getCallback(): CallbackInterface
    {
        return new ExecuteDocumentReceived($this->document);
    }
}
