<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity\Callback;

use App\Entity\Callback\CallbackInterface;
use App\Entity\Callback\DelayedCallback;
use App\Entity\Callback\ExecuteDocumentReceivedCallback;
use App\Model\BackoffStrategy\ExponentialBackoffStrategy;
use App\Model\StampCollection;
use App\Tests\Model\TestCallback;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use webignition\YamlDocument\Document;

class DelayedCallbackTest extends TestCase
{
    /**
     * @dataProvider encapsulatesDataProvider
     */
    public function testEncapsulates(CallbackInterface $callback)
    {
        $delayedCallback = new DelayedCallback($callback, new ExponentialBackoffStrategy());

        self::assertSame($callback->getRetryCount(), $delayedCallback->getRetryCount());
        self::assertSame($callback->getType(), $delayedCallback->getType());
        self::assertSame($callback->getPayload(), $delayedCallback->getPayload());
    }

    public function encapsulatesDataProvider(): array
    {
        return [
            'no explicit data, retry count 0' => [
                'callback' => (new TestCallback())
                    ->withRetryCount(0),
            ],
            'no explicit data, retry count 1' => [
                'callback' => (new TestCallback())
                    ->withRetryCount(1),
            ],
            'with explicit data, retry count 1' => [
                'callback' => (new TestCallback())
                    ->withPayload(['key' => 'value'])
                    ->withRetryCount(1),
            ],
            'with type' => [
                'callback' => new ExecuteDocumentReceivedCallback(
                    new Document('{ key: value }')
                ),
            ],
        ];
    }

    /**
     * @dataProvider getStampsDataProvider
     */
    public function testGetStamps(DelayedCallback $callback, StampCollection $expectedStamps)
    {
        self::assertEquals($expectedStamps, $callback->getStamps());
    }

    public function getStampsDataProvider(): array
    {
        return [
            'retryCount 0' => [
                'callback' => DelayedCallback::create(
                    (new TestCallback())
                        ->withRetryCount(0)
                ),
                'expectedStamps' => new StampCollection(),
            ],
            'retryCount 1' => [
                'callback' => DelayedCallback::create(
                    (new TestCallback())
                        ->withRetryCount(1)
                ),
                'expectedStamps' => new StampCollection([
                    new DelayStamp(1000),
                ]),
            ],
            'retryCount 2' => [
                'callback' => DelayedCallback::create(
                    (new TestCallback())
                        ->withRetryCount(2)
                ),
                'expectedStamps' => new StampCollection([
                    new DelayStamp(3000),
                ]),
            ],
            'retryCount 3' => [
                'callback' => DelayedCallback::create(
                    (new TestCallback())
                        ->withRetryCount(3)
                ),
                'expectedStamps' => new StampCollection([
                    new DelayStamp(7000),
                ]),
            ],
        ];
    }
}
