<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Event\SourceCompileEventInterface;
use App\Event\SourceCompileFailureEvent;
use App\Event\SourceCompileSuccessEvent;
use App\Services\SourceCompileEventFactory;
use PHPUnit\Framework\TestCase;
use webignition\BasilCompilerModels\ErrorOutputInterface;
use webignition\BasilCompilerModels\OutputInterface;
use webignition\BasilCompilerModels\SuiteManifest;

class SourceCompileEventFactoryTest extends TestCase
{
    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(string $source, OutputInterface $output, ?SourceCompileEventInterface $expectedEvent)
    {
        $factory = new SourceCompileEventFactory();

        self::assertEquals(
            $expectedEvent,
            $factory->create($source, $output)
        );
    }

    public function createDataProvider(): array
    {
        $errorOutput = \Mockery::mock(ErrorOutputInterface::class);
        $successOutput = \Mockery::mock(SuiteManifest::class);
        $unknownOutput = \Mockery::mock(OutputInterface::class);

        return [
            'error output' => [
                'source' => 'Test/test1.yml',
                'output' => $errorOutput,
                'expectedEvent' => new SourceCompileFailureEvent('Test/test1.yml', $errorOutput),
            ],
            'success output' => [
                'source' => 'Test/test2.yml',
                'output' => $successOutput,
                'expectedEvent' => new SourceCompileSuccessEvent('Test/test2.yml', $successOutput),
            ],
            'unknown output' => [
                'source' => 'Test/test1.yml',
                'output' => $unknownOutput,
                'expectedEvent' => null,
            ],
        ];
    }
}
