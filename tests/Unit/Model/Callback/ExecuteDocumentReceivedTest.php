<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model\Callback;

use App\Model\Callback\ExecuteDocumentReceived;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Dumper;
use webignition\YamlDocument\Document;

class ExecuteDocumentReceivedTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testGetType()
    {
        $callback = new ExecuteDocumentReceived(new Document());

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
        $arrayDocument = new Document((new Dumper())->dump($data));
        $nonArrayDocument = new Document('string');

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

    public function testSendAttemptCount()
    {
        $callback = new ExecuteDocumentReceived(new Document());
        self::assertSame(0, $callback->getRetryCount());

        $callback->incrementRetryCount();
        self::assertSame(1, $callback->getRetryCount());

        $callback->incrementRetryCount();
        self::assertSame(2, $callback->getRetryCount());
    }
}
