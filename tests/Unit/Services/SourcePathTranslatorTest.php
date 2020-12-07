<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Services\SourcePathTranslator;
use PHPUnit\Framework\TestCase;

class SourcePathTranslatorTest extends TestCase
{
    private const COMPILER_SOURCE_DIRECTORY = '/app/source';
    private const COMPILER_TARGET_DIRECTORY = '/app/tests';

    private SourcePathTranslator $sourcePathTranslator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sourcePathTranslator = new SourcePathTranslator(
            self::COMPILER_SOURCE_DIRECTORY,
            self::COMPILER_TARGET_DIRECTORY
        );
    }


    /**
     * @dataProvider stripCompilerSourceDirectoryDataProvider
     */
    public function testStripCompilerSourceDirectory(string $path, string $expectedPath)
    {
        self::assertSame($expectedPath, $this->sourcePathTranslator->stripCompilerSourceDirectory($path));
    }

    public function stripCompilerSourceDirectoryDataProvider(): array
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

    /**
     * @dataProvider stripCompilerTargetDirectoryDataProvider
     */
    public function testStripCompilerTargetDirectory(string $path, string $expectedPath)
    {
        self::assertSame($expectedPath, $this->sourcePathTranslator->stripCompilerTargetDirectory($path));
    }

    public function stripCompilerTargetDirectoryDataProvider(): array
    {
        return [
            'path shorter than prefix' => [
                'path' => 'short/path',
                'expectedPath' => 'short/path',
            ],
            'prefix not present' => [
                'path' => '/path/that/does/not/contain/prefix/GeneratedTest.php',
                'expectedPath' => '/path/that/does/not/contain/prefix/GeneratedTest.php',
            ],
            'prefix present' => [
                'path' => self::COMPILER_TARGET_DIRECTORY . '/GeneratedTest.php',
                'expectedPath' => 'GeneratedTest.php',
            ],
        ];
    }
}
