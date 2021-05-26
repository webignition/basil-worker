<?php

declare(strict_types=1);

namespace App\Tests\Unit\ArgumentResolver;

use App\ArgumentResolver\EncapsulatingRequestResolver;
use App\Request\AddSourcesRequest;
use App\Request\EncapsulatingRequestInterface;
use App\Request\JobCreateRequest;
use App\Tests\Mock\MockArgumentMetadata;
use App\Tests\Mock\MockUploadedFile;
use PHPUnit\Framework\TestCase;
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
    public function testSupports(ArgumentMetadata $argumentMetadata, bool $expectedSupports): void
    {
        $request = \Mockery::mock(Request::class);

        self::assertSame($expectedSupports, $this->resolver->supports($request, $argumentMetadata));
    }

    /**
     * @return array[]
     */
    public function supportsDataProvider(): array
    {
        return [
            'does support' => [
                'argumentMetadata' => (new MockArgumentMetadata())
                    ->withGetTypeCall(JobCreateRequest::class)
                    ->getMock(),
                'expectedSupports' => true,
            ],
            'does not support' => [
                'argumentMetadata' => (new MockArgumentMetadata())
                    ->withGetTypeCall('string')
                    ->getMock(),
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
    ): void {
        $generator = $this->resolver->resolve($request, $argumentMetadata);
        $encapsulatingRequest = $generator->current();

        self::assertEquals($expectedEncapsulatingRequest, $encapsulatingRequest);
    }

    /**
     * @return array[]
     */
    public function resolveJobCreateRequestDataProvider(): array
    {
        $label = 'label content';
        $callbackUrl = 'http://example.com/callback';

        $argumentMetadata = (new MockArgumentMetadata())
            ->withGetTypeCall(JobCreateRequest::class)
            ->getMock();

        return [
            'JobCreateRequest: empty' => [
                'request' => new Request(),
                'argumentMetadata' => $argumentMetadata,
                'expectedEncapsulatingRequest' => new JobCreateRequest(new Request()),
            ],
            'JobCreateRequest: callback url missing' => [
                'request' => new Request([], [
                    JobCreateRequest::KEY_LABEL => $label,
                ]),
                'argumentMetadata' => $argumentMetadata,
                'expectedEncapsulatingRequest' => new JobCreateRequest(new Request([], [
                    JobCreateRequest::KEY_LABEL => $label,
                ])),
            ],
            'JobCreateRequest: label present, callback url present' => [
                'request' => new Request([], [
                    JobCreateRequest::KEY_LABEL => $label,
                    JobCreateRequest::KEY_CALLBACK_URL => $callbackUrl,
                ]),
                'argumentMetadata' => $argumentMetadata,
                'expectedEncapsulatingRequest' => new JobCreateRequest(new Request([], [
                    JobCreateRequest::KEY_LABEL => $label,
                    JobCreateRequest::KEY_CALLBACK_URL => $callbackUrl,
                ])),
            ],
        ];
    }

    /**
     * @return array[]
     */
    public function resolveAddSourcesRequestDataProvider(): array
    {
        $manifest = (new MockUploadedFile())->getMock();
        $source1 = (new MockUploadedFile())->getMock();
        $source2 = (new MockUploadedFile())->getMock();
        $source3 = (new MockUploadedFile())->getMock();

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

        $argumentMetadata = (new MockArgumentMetadata())
            ->withGetTypeCall(AddSourcesRequest::class)
            ->getMock();

        return [
            'AddSourcesRequest: empty' => [
                'request' => new Request(),
                'argumentMetadata' => $argumentMetadata,
                'expectedEncapsulatingRequest' => new AddSourcesRequest(new Request()),
            ],
            'AddSourcesRequest: manifest only' => [
                'request' => clone $manifestOnlyRequest,
                'argumentMetadata' => $argumentMetadata,
                'expectedEncapsulatingRequest' => new AddSourcesRequest(clone $manifestOnlyRequest),
            ],
            'AddSourcesRequest: sources only' => [
                'request' => clone $sourcesOnlyRequest,
                'argumentMetadata' => $argumentMetadata,
                'expectedEncapsulatingRequest' => new AddSourcesRequest(clone $sourcesOnlyRequest),
            ],
            'AddSourcesRequest: manifest and sources' => [
                'request' => clone $manifestAndSourcesRequest,
                'argumentMetadata' => $argumentMetadata,
                'expectedEncapsulatingRequest' => new AddSourcesRequest(clone $manifestAndSourcesRequest),
            ],
        ];
    }
}
