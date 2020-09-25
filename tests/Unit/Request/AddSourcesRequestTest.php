<?php

declare(strict_types=1);

namespace App\Tests\Unit\Request;

use App\Request\AddSourcesRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

class AddSourcesRequestTest extends TestCase
{
    /**
     * @dataProvider createDataProvider
     *
     * @param Request $request
     * @param UploadedFile|null $expectedManifest
     * @param UploadedFile[] $expectedSources
     */
    public function testCreate(Request $request, ?UploadedFile $expectedManifest, array $expectedSources)
    {
        $addSourcesRequest = new AddSourcesRequest($request);

        self::assertSame($expectedManifest, $addSourcesRequest->getManifest());
        self::assertSame($expectedSources, $addSourcesRequest->getSources());
    }

    public function createDataProvider(): array
    {
        $manifest = \Mockery::mock(UploadedFile::class);
        $source1 = \Mockery::mock(UploadedFile::class);
        $source2 = \Mockery::mock(UploadedFile::class);
        $source3 = \Mockery::mock(UploadedFile::class);

        return [
            'empty' => [
                'request' => new Request(),
                'expectedManifest' => null,
                'expectedSources' => [],
            ],
            'manifest present only' => [
                'request' => new Request(
                    [],
                    [],
                    [],
                    [],
                    [
                        AddSourcesRequest::KEY_MANIFEST => $manifest,
                    ]
                ),
                'expectedManifest' => $manifest,
                'expectedSources' => [],
            ],
            'sources present only' => [
                'request' => new Request(
                    [],
                    [],
                    [],
                    [],
                    [
                        'test1.yml' => $source1,
                        'test2.yml' => $source2,
                        'test3.yml' => $source3,
                    ]
                ),
                'expectedManifest' => null,
                'expectedSources' => [
                    'test1.yml' => $source1,
                    'test2.yml' => $source2,
                    'test3.yml' => $source3,
                ],
            ],
            'manifest and sources present' => [
                'request' => new Request(
                    [],
                    [],
                    [],
                    [],
                    [
                        AddSourcesRequest::KEY_MANIFEST => $manifest,
                        'test1.yml' => $source1,
                        'test2.yml' => $source2,
                        'test3.yml' => $source3,
                    ]
                ),
                'expectedManifest' => $manifest,
                'expectedSources' => [
                    'test1.yml' => $source1,
                    'test2.yml' => $source2,
                    'test3.yml' => $source3,
                ],
            ],
        ];
    }
}
