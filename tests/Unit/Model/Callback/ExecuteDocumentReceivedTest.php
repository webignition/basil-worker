<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model\Callback;

use App\Model\Callback\ExecuteDocumentReceived;
use App\Tests\Mock\MockYamlDocument;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class ExecuteDocumentReceivedTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testGetType()
    {
        $callback = new ExecuteDocumentReceived((new MockYamlDocument())->getMock());

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

        $arrayDocument = (new MockYamlDocument())
            ->withParseCall($data)
            ->getMock();

        $nonArrayDocument = (new MockYamlDocument())
            ->withParseCall('string')
            ->getMock();

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
