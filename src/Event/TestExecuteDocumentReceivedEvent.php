<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Callback\CallbackInterface;
use App\Entity\Callback\ExecuteDocumentReceivedCallback;
use App\Entity\Test;
use webignition\YamlDocument\Document;

class TestExecuteDocumentReceivedEvent extends AbstractTestEvent implements CallbackEventInterface
{
    private Document $document;
    private CallbackInterface $callback;

    public function __construct(Test $test, Document $document, ExecuteDocumentReceivedCallback $callback)
    {
        parent::__construct($test);

        $this->document = $document;
        $this->callback = $callback;
    }

    public function getDocument(): Document
    {
        return $this->document;
    }

    public function getCallback(): CallbackInterface
    {
        return $this->callback;
    }
}
