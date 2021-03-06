<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Test;
use Symfony\Contracts\EventDispatcher\Event;
use webignition\YamlDocument\Document;

abstract class AbstractTestEvent extends Event implements TestEventInterface
{
    public function __construct(private Test $test, private Document $document)
    {
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
