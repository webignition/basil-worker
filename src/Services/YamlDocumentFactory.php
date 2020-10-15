<?php

declare(strict_types=1);

namespace App\Services;

use webignition\YamlDocument\Document;

class YamlDocumentFactory
{
    private Document $currentDocument;

    /**
     * @var callable
     */
    private $onDocumentCreated;

    public function __construct()
    {
        $this->currentDocument = new Document();
        $this->resetOnDocumentCreated();
    }

    public function setOnDocumentCreated(callable $onDocumentCreated): void
    {
        $this->onDocumentCreated = $onDocumentCreated;
    }

    public function start(): void
    {
        $this->currentDocument = new Document();
    }

    public function resetOnDocumentCreated(): void
    {
        $this->onDocumentCreated = function (Document $document) {
        };
    }

    public function process(string $content): void
    {
        $content = rtrim($content, "\n");

        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $this->processLine($line);
        }
    }

    public function stop(): void
    {
        if (false === $this->currentDocument->isEmpty()) {
            ($this->onDocumentCreated)($this->currentDocument);
        }
    }

    private function processLine(string $line): void
    {
        $isDocumentStart = Document::isDocumentStart($line);
        $isDocumentEnd = Document::isDocumentEnd($line);

        if ($isDocumentStart) {
            if (false === $this->currentDocument->isEmpty()) {
                ($this->onDocumentCreated)($this->currentDocument);
            }

            $this->currentDocument = new Document();
        }

        if (false === $isDocumentStart && false === $isDocumentEnd) {
            $this->currentDocument = $this->currentDocument->append($line . "\n");
        }
    }
}
