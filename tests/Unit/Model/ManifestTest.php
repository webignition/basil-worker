<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model;

use App\Model\Manifest;
use App\Tests\Mock\MockUploadedFile;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use phpmock\mockery\PHPMockery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ManifestTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @dataProvider createDataProvider
     *
     * @param UploadedFile $uploadedFile
     * @param string[] $expectedTestPaths
     */
    public function testCreate(
        UploadedFile $uploadedFile,
        string $manifestContent,
        array $expectedTestPaths
    ) {
        PHPMockery::mock('App\Model', 'file_get_contents')
            ->andReturn($manifestContent);

        $manifest = new Manifest($uploadedFile);

        self::assertSame($expectedTestPaths, $manifest->getTestPaths());
    }

    public function createDataProvider(): array
    {
        $uploadedFile = (new MockUploadedFile())
            ->withGetErrorCall(0)
            ->withGetPathnameCall('/tmp/manifest.txt')
            ->getMock();

        return [
            'empty' => [
                'uploadedFile' => $uploadedFile,
                'manifestContent' => '',
                'expectedTestPaths' => [],
            ],
            'non-empty' => [
                'uploadedFile' => $uploadedFile,
                'manifestContent' => 'Test/test1.yml' . "\n" .
                    'Test/test2.yml' . "\n" .
                    '' . "\n" .
                    '  ' . "\n" .
                    ' Test/test3.yml ',
                'expectedTestPaths' => [
                    'Test/test1.yml',
                    'Test/test2.yml',
                    'Test/test3.yml',
                ],
            ],
        ];
    }
}
