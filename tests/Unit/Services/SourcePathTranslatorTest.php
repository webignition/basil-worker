<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Services\SourcePathTranslator;
use PHPUnit\Framework\TestCase;

class SourcePathTranslatorTest extends TestCase
{
    private const COMPILER_SOURCE_DIRECTORY = '/app/source';

    private SourcePathTranslator $sourcePathTranslator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sourcePathTranslator = new SourcePathTranslator(self::COMPILER_SOURCE_DIRECTORY);
    }

    /**
     * @dataProvider isPrefixedWithCompilerSourceDirectoryDataProvider
     */
    public function testIsPrefixedWithCompilerSourceDirectory(string $path, bool $expectedIsPrefixed)
    {
        self::assertSame(
            $expectedIsPrefixed,
            $this->sourcePathTranslator->isPrefixedWithCompilerSourceDirectory($path)
        );
    }

    public function isPrefixedWithCompilerSourceDirectoryDataProvider(): array
    {
        return [
            'path shorter than prefix' => [
                'path' => 'short/path',
                'expectedIsPrefixed' => false,
            ],
            'prefix not present' => [
                'path' => '/path/that/does/not/contain/prefix/test.yml',
                'expectedIsPrefixed' => false,
            ],
            'prefix present' => [
                'path' => self::COMPILER_SOURCE_DIRECTORY . '/Test/test.yml',
                'expectedIsPrefixed' => true,
            ],
        ];
    }

    /**
     * @dataProvider stripCompilerSourceDirectoryFromPathDataProvider
     */
    public function testStripCompilerSourceDirectoryFromPath(string $path, string $expectedPath)
    {
        self::assertSame($expectedPath, $this->sourcePathTranslator->stripCompilerSourceDirectoryFromPath($path));
    }

    public function stripCompilerSourceDirectoryFromPathDataProvider(): array
    {
        return [
            'path shorter than prefix' => [
                'path' => 'short/path',
                'expectedPath' => 'short/path',
            ],
            'prefix not present' => [
                'path' => '/path/that/does/not/contain/prefix/test.yml',
                'expectedPath' => '/path/that/does/not/contain/prefix/test.yml',
            ],
            'prefix present' => [
                'path' => self::COMPILER_SOURCE_DIRECTORY . '/Test/test.yml',
                'expectedPath' => 'Test/test.yml',
            ],
        ];
    }
}
