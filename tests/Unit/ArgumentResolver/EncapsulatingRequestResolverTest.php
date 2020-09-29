<?php

declare(strict_types=1);

namespace App\Tests\Unit\ArgumentResolver;

use App\ArgumentResolver\EncapsulatingRequestResolver;
use App\Request\AddSourcesRequest;
use App\Request\EncapsulatingRequestInterface;
use App\Request\JobCreateRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class EncapsulatingRequestResolverTest extends TestCase
{
    private EncapsulatingRequestResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new EncapsulatingRequestResolver();
    }

    /**
     * @dataProvider supportsDataProvider
     */
    public function testSupports(ArgumentMetadata $argumentMetadata, bool $expectedSupports)
    {
        $request = \Mockery::mock(Request::class);

        self::assertSame($expectedSupports, $this->resolver->supports($request, $argumentMetadata));
    }

    public function supportsDataProvider(): array
    {
        return [
            'does support' => [
                'argumentMetadata' => $this->createArgumentMetadata(JobCreateRequest::class),
                'expectedSupports' => true,
            ],
            'does not support' => [
                'argumentMetadata' => $this->createArgumentMetadata('string'),
                'expectedSupports' => false,
            ],
        ];
    }

    /**
     * @dataProvider resolveJobCreateRequestDataProvider
     * @dataProvider resolveAddSourcesRequestDataProvider
     */
    public function testResolve(
        Request $request,
        ArgumentMetadata $argumentMetadata,
        EncapsulatingRequestInterface $expectedEncapsulatingRequest
    ) {
        $generator = $this->resolver->resolve($request, $argumentMetadata);
        $encapsulatingRequest = $generator->current();

        self::assertEquals($expectedEncapsulatingRequest, $encapsulatingRequest);
    }

    public function resolveJobCreateRequestDataProvider(): array
    {
        $label = 'label content';
        $callbackUrl = 'http://example.com/callback';

        return [
            'JobCreateRequest: empty' => [
                'request' => new Request(),
                'argumentMetadata' => $this->createArgumentMetadata(JobCreateRequest::class),
                'expectedEncapsulatingRequest' => new JobCreateRequest(new Request()),
            ],
            'JobCreateRequest: callback url missing' => [
                'request' => new Request([], [
                    JobCreateRequest::KEY_LABEL => $label,
                ]),
                'argumentMetadata' => $this->createArgumentMetadata(JobCreateRequest::class),
                'expectedEncapsulatingRequest' => new JobCreateRequest(new Request([], [
                    JobCreateRequest::KEY_LABEL => $label,
                ])),
            ],
            'JobCreateRequest: label present, callback url present' => [
                'request' => new Request([], [
                    JobCreateRequest::KEY_LABEL => $label,
                    JobCreateRequest::KEY_CALLBACK_URL => $callbackUrl,
                ]),
                'argumentMetadata' => $this->createArgumentMetadata(JobCreateRequest::class),
                'expectedEncapsulatingRequest' => new JobCreateRequest(new Request([], [
                    JobCreateRequest::KEY_LABEL => $label,
                    JobCreateRequest::KEY_CALLBACK_URL => $callbackUrl,
                ])),
            ],
        ];
    }

    public function resolveAddSourcesRequestDataProvider(): array
    {
        $manifest = \Mockery::mock(UploadedFile::class);
        $source1 = \Mockery::mock(UploadedFile::class);
        $source2 = \Mockery::mock(UploadedFile::class);
        $source3 = \Mockery::mock(UploadedFile::class);

        $manifestOnlyRequest = new Request(
            [],
            [],
            [],
            [],
            [
                AddSourcesRequest::KEY_MANIFEST => $manifest,
            ]
        );

        $sourcesOnlyRequest = new Request(
            [],
            [],
            [],
            [],
            [
                'test1.yml' => $source1,
                'test2.yml' => $source2,
                'test3.yml' => $source3,
            ]
        );

        $manifestAndSourcesRequest = new Request(
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
        );

        return [
            'AddSourcesRequest: empty' => [
                'request' => new Request(),
                'argumentMetadata' => $this->createArgumentMetadata(AddSourcesRequest::class),
                'expectedEncapsulatingRequest' => new AddSourcesRequest(new Request()),
            ],
            'AddSourcesRequest: manifest only' => [
                'request' => clone $manifestOnlyRequest,
                'argumentMetadata' => $this->createArgumentMetadata(AddSourcesRequest::class),
                'expectedEncapsulatingRequest' => new AddSourcesRequest(clone $manifestOnlyRequest),
            ],
            'AddSourcesRequest: sources only' => [
                'request' => clone $sourcesOnlyRequest,
                'argumentMetadata' => $this->createArgumentMetadata(AddSourcesRequest::class),
                'expectedEncapsulatingRequest' => new AddSourcesRequest(clone $sourcesOnlyRequest),
            ],
            'AddSourcesRequest: manifest and sources' => [
                'request' => clone $manifestAndSourcesRequest,
                'argumentMetadata' => $this->createArgumentMetadata(AddSourcesRequest::class),
                'expectedEncapsulatingRequest' => new AddSourcesRequest(clone $manifestAndSourcesRequest),
            ],
        ];
    }

    private function createArgumentMetadata(string $type): ArgumentMetadata
    {
        $metadata = \Mockery::mock(ArgumentMetadata::class);
        $metadata
            ->shouldReceive('getType')
            ->andReturn($type);

        return $metadata;
    }
}
