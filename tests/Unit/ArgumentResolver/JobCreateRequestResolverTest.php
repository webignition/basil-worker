<?php

declare(strict_types=1);

namespace App\Tests\Unit\ArgumentResolver;

use App\ArgumentResolver\JobCreateRequestResolver;
use App\Exception\JobCreateRequestException;
use App\Request\JobCreateRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class JobCreateRequestResolverTest extends TestCase
{
    private JobCreateRequestResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new JobCreateRequestResolver();
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
     * @dataProvider resolveThrowsExceptionDataProvider
     */
    public function testResolveThrowsException(Request $request, JobCreateRequestException $expectedException)
    {
        self::expectExceptionObject($expectedException);

        $generator = $this->resolver->resolve($request, \Mockery::mock(ArgumentMetadata::class));
        $generator->current();
    }

    public function resolveThrowsExceptionDataProvider(): array
    {
        return [
            'label missing' => [
                'request' => new Request(),
                'expectedException' => JobCreateRequestException::createLabelMissingException(),
            ],
            'callback-url missing' => [
                'request' => new Request([], [
                    JobCreateRequest::KEY_LABEL => 'label content',
                ]),
                'expectedException' => JobCreateRequestException::createCallbackUrlMissingException(),
            ],
        ];
    }

    /**
     * @dataProvider resolveSuccessDataProvider
     */
    public function testResolveSuccess(Request $request, JobCreateRequest $expectedJobCreateRequest)
    {
        $generator = $this->resolver->resolve($request, \Mockery::mock(ArgumentMetadata::class));
        $jobCreateRequest = $generator->current();

        self::assertEquals($expectedJobCreateRequest, $jobCreateRequest);
    }

    public function resolveSuccessDataProvider(): array
    {
        $request = new Request([], [
            JobCreateRequest::KEY_LABEL => 'label content',
            JobCreateRequest::KEY_CALLBACK_URL => 'http://example.com/callback',
        ]);

        return [
            'label present, callback-url present' => [
                'request' => $request,
                'expectedJobCreateRequest' => new JobCreateRequest($request),
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
