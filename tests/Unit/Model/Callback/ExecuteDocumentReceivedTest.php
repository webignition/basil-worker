<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model\Callback;

use App\Model\Callback\ExecuteDocumentReceived;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use webignition\YamlDocument\Document;

class ExecuteDocumentReceivedTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testGetType()
    {
        $callback = new ExecuteDocumentReceived(\Mockery::mock(Document::class));

        self::assertSame(ExecuteDocumentReceived::TYPE, $callback->getType());
    }

    /**
     * @dataProvider getDataDataProvider
     *
     * @param ExecuteDocumentReceived $callback
     * @param array<mixed> $expectedData
     */
    public function testGetData(ExecuteDocumentReceived $callback, array $expectedData)
    {
        self::assertSame($expectedData, $callback->getData());
    }

    public function getDataDataProvider(): array
    {
        $data = [
            'key1' => 'value1',
            'key2' => 'value2',
        ];

        $arrayDocument = \Mockery::mock(Document::class);
        $arrayDocument
            ->shouldReceive('parse')
            ->withNoArgs()
            ->andReturn($data);

        $nonArrayDocument = \Mockery::mock(Document::class);
        $nonArrayDocument
            ->shouldReceive('parse')
            ->withNoArgs()
            ->andReturn('string');

        return [
            'document is an array' => [
                'callback' => new ExecuteDocumentReceived($arrayDocument),
                'expectedData' => $data,
            ],
            'document not an array' => [
                'callback' => new ExecuteDocumentReceived($nonArrayDocument),
                'expectedData' => [],
            ],
        ];
    }
}
