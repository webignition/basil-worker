<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model;

use App\Model\Manifest;
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
        return [
            'empty' => [
                'uploadedFile' => $this->createUploadedFile(
                    'Test/test1.yml',
                    0
                ),
                'manifestContent' => '',
                'expectedTestPaths' => [],
            ],
            'non-empty' => [
                'uploadedFile' => $this->createUploadedFile(
                    '/tmp/manifest.txt',
                    0
                ),
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

    private function createUploadedFile(string $pathname, int $error = 0): UploadedFile
    {
        $uploadedFile = \Mockery::mock(UploadedFile::class);

        $uploadedFile
            ->shouldReceive('getPathname')
            ->andReturn($pathname);

        $uploadedFile
            ->shouldReceive('getError')
            ->andReturn($error);

        return $uploadedFile;
    }
}
