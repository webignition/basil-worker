<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Model\Callback\ExecuteDocumentReceived;
use App\Services\ExecuteDocumentReceivedCallbackFactory;
use App\Services\SourcePathTranslator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Dumper;
use webignition\YamlDocument\Document;

class ExecuteDocumentReceivedCallbackFactoryTest extends TestCase
{
    private const COMPILER_SOURCE_DIRECTORY = '/app/source';

    private ExecuteDocumentReceivedCallbackFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new ExecuteDocumentReceivedCallbackFactory(
            new SourcePathTranslator(self::COMPILER_SOURCE_DIRECTORY),
            new Dumper()
        );
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(Document $document, ExecuteDocumentReceived $expectedCallback)
    {
        $callback = $this->factory->create($document);

        self::assertEquals($expectedCallback, $callback);
    }

    public function createDataProvider(): array
    {
        $step = new Document('{ type: step }');
        $testWithoutPrefixedPath = new Document('{ type: test, path: /path/to/test.yml }');

        return [
            'document is step' => [
                'document' => $step,
                'expectedCallback' => new ExecuteDocumentReceived($step),
            ],
            'test without prefixed path' => [
                'document' => $testWithoutPrefixedPath,
                'expectedCallback' => new ExecuteDocumentReceived($testWithoutPrefixedPath),
            ],
            'test with prefixed path' => [
                'document' => new Document(
                    '{ type: test, path: ' . self::COMPILER_SOURCE_DIRECTORY . '/Test/test.yml }'
                ),
                'expectedCallback' => new ExecuteDocumentReceived(new Document('{ type: test, path: Test/test.yml }')),
            ],
        ];
    }
}
