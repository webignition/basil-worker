<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model;

use App\Model\StampCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\StampInterface;
use Symfony\Component\Messenger\Stamp\ValidationStamp;

class StampCollectionTest extends TestCase
{
    /**
     * @dataProvider getStampsDataProvider
     *
     * @param StampCollection $collection
     * @param StampInterface[] $expectedStamps
     */
    public function testGetStamps(StampCollection $collection, array $expectedStamps)
    {
        self::assertSame($expectedStamps, $collection->getStamps());
    }

    public function getStampsDataProvider(): array
    {
        $delayStamp = new DelayStamp(1000);
        $validationStamp = new ValidationStamp([]);

        return [
            'empty' => [
                'collection' => new StampCollection(),
                'expectedStamps' => [],
            ],
            'non-empty' => [
                'collection' => new StampCollection([
                    true,
                    'string',
                    100,
                    $delayStamp,
                    $validationStamp,
                ]),
                'expectedStamps' => [
                    $delayStamp,
                    $validationStamp,
                ],
            ],
        ];
    }

    /**
     * @dataProvider countDataProvider
     */
    public function testCount(StampCollection $collection, int $expectedCount)
    {
        self::assertCount($expectedCount, $collection);
    }

    public function countDataProvider(): array
    {
        return [
            'empty' => [
                'collection' => new StampCollection(),
                'expectedCount' => 0,
            ],
            'non-empty' => [
                'collection' => new StampCollection([
                    true,
                    'string',
                    100,
                    new DelayStamp(1000),
                    new ValidationStamp([]),
                ]),
                'expectedCount' => 2,
            ],
        ];
    }

    /**
     * @dataProvider hasStampsDataProvider
     */
    public function testHasStamps(StampCollection $collection, bool $expectedIsEmpty)
    {
        self::assertSame($expectedIsEmpty, $collection->hasStamps());
    }

    public function hasStampsDataProvider(): array
    {
        return [
            'empty' => [
                'collection' => new StampCollection(),
                'expectedHasStamps' => false,
            ],
            'non-empty' => [
                'collection' => new StampCollection([
                    true,
                    'string',
                    100,
                    new DelayStamp(1000),
                    new ValidationStamp([]),
                ]),
                'expectedHasStamps' => true,
            ],
        ];
    }
}
