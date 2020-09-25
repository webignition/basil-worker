<?php

declare(strict_types=1);

namespace App\Tests\Unit\ArgumentResolver;

use App\ArgumentResolver\JobCreateRequestResolver;
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
     * @dataProvider resolveDataProvider
     */
    public function testResolve(Request $request, JobCreateRequest $expectedJobCreateRequest)
    {
        $generator = $this->resolver->resolve($request, \Mockery::mock(ArgumentMetadata::class));
        $jobCreateRequest = $generator->current();

        self::assertEquals($expectedJobCreateRequest, $jobCreateRequest);
    }

    public function resolveDataProvider(): array
    {
        $label = 'label content';
        $callbackUrl = 'http://example.com/callback';

        return [
            'label missing' => [
                'request' => new Request(),
                'expectedJobCreateRequest' => new JobCreateRequest(new Request()),
            ],
            'callback-url missing' => [
                'request' => new Request([], [
                    JobCreateRequest::KEY_LABEL => $label,
                ]),
                'expectedJobCreateRequest' => new JobCreateRequest(new Request([], [
                    JobCreateRequest::KEY_LABEL => $label,
                ])),
            ],
            'label present, callback-url present' => [
                'request' => new Request([], [
                    JobCreateRequest::KEY_LABEL => $label,
                    JobCreateRequest::KEY_CALLBACK_URL => $callbackUrl,
                ]),
                'expectedJobCreateRequest' => new JobCreateRequest(new Request([], [
                    JobCreateRequest::KEY_LABEL => $label,
                    JobCreateRequest::KEY_CALLBACK_URL => $callbackUrl,
                ])),
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
