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

    /**
     * @dataProvider stripCompilerTargetDirectoryFromPathDataProvider
     */
    public function testStripCompilerTargetDirectoryFromPath(string $path, string $expectedPath)
    {
        self::assertSame($expectedPath, $this->sourcePathTranslator->stripCompilerTargetDirectoryFromPath($path));
    }

    public function stripCompilerTargetDirectoryFromPathDataProvider(): array
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
