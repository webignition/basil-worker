<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use Mockery\MockInterface;
use webignition\YamlDocument\Document;

class MockYamlDocument
{
    /**
     * @var Document|MockInterface
     */
    private Document $document;

    public function __construct()
    {
        $this->document = \Mockery::mock(Document::class);
    }

    public function getMock(): Document
    {
        return $this->document;
    }

    /**
     * @param mixed $data
     *
     * @return $this
     */
    public function withParseCall($data): self
    {
        $this->document
            ->shouldReceive('parse')
            ->withNoArgs()
            ->andReturn($data);

        return $this;
    }
}
