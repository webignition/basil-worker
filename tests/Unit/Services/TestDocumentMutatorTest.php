<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Services\SourcePathTranslator;
use App\Services\TestDocumentMutator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Dumper;
use webignition\YamlDocument\Document;

class TestDocumentMutatorTest extends TestCase
{
    private const COMPILER_SOURCE_DIRECTORY = '/app/source';
    private const COMPILER_TARGET_DIRECTORY = '/app/tests';

    private TestDocumentMutator $mutator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mutator = new TestDocumentMutator(
            new SourcePathTranslator(
                self::COMPILER_SOURCE_DIRECTORY,
                self::COMPILER_TARGET_DIRECTORY
            ),
            new Dumper()
        );
    }

    /**
     * @dataProvider removeCompilerSourceDirectoryFromSourceDataProvider
     */
    public function testRemoveCompilerSourceDirectoryFromSource(Document $document, Document $expectedDocument)
    {
        $mutatedDocument = $this->mutator->removeCompilerSourceDirectoryFromSource($document);

        self::assertEquals($mutatedDocument, $expectedDocument);
    }

    public function removeCompilerSourceDirectoryFromSourceDataProvider(): array
    {
        $step = new Document('{ type: step }');
        $testWithoutPrefixedPath = new Document('{ type: test, path: /path/to/test.yml }');

        return [
            'document is step' => [
                'document' => $step,
                'expectedDocument' => $step,
            ],
            'test without prefixed path' => [
                'document' => $testWithoutPrefixedPath,
                'expectedDocument' => $testWithoutPrefixedPath,
            ],
            'test with prefixed path' => [
                'document' => new Document(
                    '{ type: test, path: ' . self::COMPILER_SOURCE_DIRECTORY . '/Test/test.yml }'
                ),
                'expectedDocument' => new Document('{ type: test, path: Test/test.yml }'),
            ],
        ];
    }
}
