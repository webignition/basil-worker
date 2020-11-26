<?php

namespace App\Tests\Unit\Controller;

use App\Controller\JobController;
use App\Request\AddSourcesRequest;
use App\Request\JobCreateRequest;
use App\Response\BadAddSourcesRequestResponse;
use App\Response\BadJobCreateRequestResponse;
use App\Services\JobStore;
use App\Services\SourceStore;
use App\Tests\Mock\Entity\MockJob;
use App\Tests\Mock\Model\MockManifest;
use App\Tests\Mock\Request\MockAddSourcesRequest;
use App\Tests\Mock\Request\MockJobCreateRequest;
use App\Tests\Mock\Services\MockJobStore;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class JobControllerTest extends TestCase
{
    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(
        JobCreateRequest $jobCreateRequest,
        JobStore $jobStore,
        JsonResponse $expectedResponse
    ) {
        $controller = new JobController($jobStore);

        $response = $controller->create($jobCreateRequest);

        self::assertSame(
            $expectedResponse->getStatusCode(),
            $response->getStatusCode()
        );

        self::assertSame(
            json_decode((string) $expectedResponse->getContent(), true),
            json_decode((string) $response->getContent(), true)
        );
    }

    public function createDataProvider(): array
    {
        return [
            'label missing' => [
                'jobCreateRequest' => (new MockJobCreateRequest())
                    ->withGetLabelCall('')
                    ->getMock(),
                'jobStore' => (new MockJobStore())->getMock(),
                'expectedResponse' => BadJobCreateRequestResponse::createLabelMissingResponse(),
            ],
            'callback url missing' => [
                'jobCreateRequest' => (new MockJobCreateRequest())
                    ->withGetLabelCall('label')
                    ->withGetCallbackUrlCall('')
                    ->getMock(),
                'jobStore' => (new MockJobStore())->getMock(),
                'expectedResponse' => BadJobCreateRequestResponse::createCallbackUrlMissingResponse(),
            ],
            'maximum duration missing' => [
                'jobCreateRequest' => (new MockJobCreateRequest())
                    ->withGetLabelCall('label')
                    ->withGetCallbackUrlCall('http://example.com')
                    ->withGetMaximumDurationInSecondsCall(null)
                    ->getMock(),
                'jobStore' => (new MockJobStore())->getMock(),
                'expectedResponse' => BadJobCreateRequestResponse::createMaximumDurationMissingResponse(),
            ],
            'job already exists' => [
                'jobCreateRequest' => (new MockJobCreateRequest())
                    ->withGetLabelCall('label')
                    ->withGetCallbackUrlCall('http://example.com')
                    ->withGetMaximumDurationInSecondsCall(10)
                    ->getMock(),
                'jobStore' => (new MockJobStore())
                    ->withHasJobCall(true)
                    ->getMock(),
                'expectedResponse' => BadJobCreateRequestResponse::createJobAlreadyExistsResponse(),
            ],
            'created' => [
                'jobCreateRequest' => (new MockJobCreateRequest())
                    ->withGetLabelCall('label')
                    ->withGetCallbackUrlCall('http://example.com')
                    ->withGetMaximumDurationInSecondsCall(10)
                    ->getMock(),
                'jobStore' => (new MockJobStore())
                    ->withHasJobCall(false)
                    ->withCreateCall('label', 'http://example.com', 10)
                    ->getMock(),
                'expectedResponse' => new JsonResponse(),
            ],
        ];
    }

    /**
     * @dataProvider addSourcesDataProvider
     */
    public function testAddSources(
        AddSourcesRequest $addSourcesRequest,
        JobStore $jobStore,
        JsonResponse $expectedResponse
    ) {
        $controller = new JobController($jobStore);

        $response = $controller->addSources(
            \Mockery::mock(SourceStore::class),
            \Mockery::mock(EventDispatcherInterface::class),
            $addSourcesRequest
        );

        self::assertSame(
            $expectedResponse->getStatusCode(),
            $response->getStatusCode()
        );

        self::assertSame(
            json_decode((string) $expectedResponse->getContent(), true),
            json_decode((string) $response->getContent(), true)
        );
    }

    public function addSourcesDataProvider(): array
    {
        $jobWithNoSources = (new MockJob())
            ->withGetSourcesCall([])
            ->getMock();

        $nonEmptyManifest = (new MockManifest())
            ->withGetTestPathsCall([
                'Test/test1.yml',
            ])
            ->getMock();

        return [
            'job missing' => [
                'addSourcesRequest' => \Mockery::mock(AddSourcesRequest::class),
                'jobStore' => (new MockJobStore())
                    ->withHasJobCall(false)
                    ->getMock(),
                'expectedResponse' => BadAddSourcesRequestResponse::createJobMissingResponse(),
            ],
            'job has sources' => [
                'addSourcesRequest' => \Mockery::mock(AddSourcesRequest::class),
                'jobStore' => (new MockJobStore())
                    ->withHasJobCall(true)
                    ->withGetJobCall(
                        (new MockJob())
                            ->withGetSourcesCall(['Test/test1.yml'])
                            ->getMock()
                    )
                    ->getMock(),
                'expectedResponse' => BadAddSourcesRequestResponse::createSourcesNotEmptyResponse(),
            ],
            'request manifest missing' => [
                'addSourcesRequest' => (new MockAddSourcesRequest())
                    ->withGetManifestCall(null)
                    ->getMock(),
                'jobStore' => (new MockJobStore())
                    ->withHasJobCall(true)
                    ->withGetJobCall($jobWithNoSources)
                    ->getMock(),
                'expectedResponse' => BadAddSourcesRequestResponse::createManifestMissingResponse(),
            ],
            'request manifest empty' => [
                'addSourcesRequest' => (new MockAddSourcesRequest())
                    ->withGetManifestCall(
                        (new MockManifest())
                            ->withGetTestPathsCall([])
                            ->getMock()
                    )
                    ->getMock(),
                'jobStore' => (new MockJobStore())
                    ->withHasJobCall(true)
                    ->withGetJobCall($jobWithNoSources)
                    ->getMock(),
                'expectedResponse' => BadAddSourcesRequestResponse::createManifestEmptyResponse(),
            ],
            'request source missing' => [
                'addSourcesRequest' => (new MockAddSourcesRequest())
                    ->withGetManifestCall($nonEmptyManifest)
                    ->withGetSourcesCall([])
                    ->getMock(),
                'jobStore' => (new MockJobStore())
                    ->withHasJobCall(true)
                    ->withGetJobCall($jobWithNoSources)
                    ->getMock(),
                'expectedResponse' => BadAddSourcesRequestResponse::createSourceMissingResponse('Test/test1.yml'),
            ],
            'request source not UploadedFile instance' => [
                'addSourcesRequest' => (new MockAddSourcesRequest())
                    ->withGetManifestCall($nonEmptyManifest)
                    ->withGetSourcesCall([
                        'Test/test1.yml' => 'not UploadedFile instance',
                    ])
                    ->getMock(),
                'jobStore' => (new MockJobStore())
                    ->withHasJobCall(true)
                    ->withGetJobCall($jobWithNoSources)
                    ->getMock(),
                'expectedResponse' => BadAddSourcesRequestResponse::createSourceMissingResponse('Test/test1.yml'),
            ],
        ];
    }
}
