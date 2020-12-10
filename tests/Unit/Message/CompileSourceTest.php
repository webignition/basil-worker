<?php

declare(strict_types=1);

namespace App\Tests\Unit\Message;

use App\Message\CompileSource;
use PHPUnit\Framework\TestCase;

class CompileSourceTest extends TestCase
{
    private const PATH = 'Test/test.yml';

    private CompileSource $message;

    protected function setUp(): void
    {
        parent::setUp();

        $this->message = new CompileSource(self::PATH);
    }

    public function testGetPath()
    {
        self::assertSame(self::PATH, $this->message->getPath());
    }

    public function testGetType()
    {
        self::assertSame(CompileSource::TYPE, $this->message->getType());
    }

    public function testGetPayload()
    {
        self::assertSame(
            [
                'path' => $this->message->getPath(),
            ],
            $this->message->getPayload()
        );
    }

    public function testJsonSerialize()
    {
        self::assertSame(
            [
                'type' => CompileSource::TYPE,
                'payload' => [
                    'path' => $this->message->getPath(),
                ],
            ],
            $this->message->jsonSerialize()
        );
    }
}
