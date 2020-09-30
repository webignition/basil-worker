<?php

namespace App\Tests\Unit\Controller;

use App\Controller\JobController;
use App\Entity\Job;
use App\Model\Manifest;
use App\Request\AddSourcesRequest;
use App\Request\JobCreateRequest;
use App\Response\BadAddSourcesRequestResponse;
use App\Response\BadJobCreateRequestResponse;
use App\Services\JobStore;
use App\Services\SourceStore;
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
                'jobCreateRequest' => $this->createJobCreateRequest('', ''),
                'jobStore' => \Mockery::mock(JobStore::class),
                'expectedResponse' => BadJobCreateRequestResponse::createLabelMissingResponse(),
            ],
            'callback url missing' => [
                'jobCreateRequest' => $this->createJobCreateRequest('label', ''),
                'jobStore' => \Mockery::mock(JobStore::class),
                'expectedResponse' => BadJobCreateRequestResponse::createCallbackUrlMissingResponse(),
            ],
            'job already exists' => [
                'jobCreateRequest' => $this->createJobCreateRequest('label', 'http://example.com'),
                'jobStore' => (new MockJobStore())
                    ->withHasJobCall(true)
                    ->getMock(),
                'expectedResponse' => BadJobCreateRequestResponse::createJobAlreadyExistsResponse(),
            ],
            'created' => [
                'jobCreateRequest' => $this->createJobCreateRequest('label', 'http://example.com'),
                'jobStore' => (new MockJobStore())
                    ->withHasJobCall(false)
                    ->withCreateCall('label', 'http://example.com')
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
                    ->withGetJobCall($this->createJob([
                        'Test/test1.yml',
                    ]))
                    ->getMock(),
                'expectedResponse' => BadAddSourcesRequestResponse::createSourcesNotEmptyResponse(),
            ],
            'request manifest missing' => [
                'addSourcesRequest' => $this->createAddSourcesRequest(null, []),
                'jobStore' => (new MockJobStore())
                    ->withHasJobCall(true)
                    ->withGetJobCall($this->createJob([]))
                    ->getMock(),
                'expectedResponse' => BadAddSourcesRequestResponse::createManifestMissingResponse(),
            ],
            'request manifest empty' => [
                'addSourcesRequest' => $this->createAddSourcesRequest(
                    $this->createManifest([]),
                    []
                ),
                'jobStore' => (new MockJobStore())
                    ->withHasJobCall(true)
                    ->withGetJobCall($this->createJob([]))
                    ->getMock(),
                'expectedResponse' => BadAddSourcesRequestResponse::createManifestEmptyResponse(),
            ],
            'request source missing' => [
                'addSourcesRequest' => $this->createAddSourcesRequest(
                    $this->createManifest([
                        'Test/test1.yml',
                    ]),
                    []
                ),
                'jobStore' => (new MockJobStore())
                    ->withHasJobCall(true)
                    ->withGetJobCall($this->createJob([]))
                    ->getMock(),
                'expectedResponse' => BadAddSourcesRequestResponse::createSourceMissingResponse('Test/test1.yml'),
            ],
            'request source not UploadedFile instance' => [
                'addSourcesRequest' => $this->createAddSourcesRequest(
                    $this->createManifest([
                        'Test/test1.yml',
                    ]),
                    [
                        'Test/test1.yml' => 'not UploadedFile instance',
                    ]
                ),
                'jobStore' => (new MockJobStore())
                    ->withHasJobCall(true)
                    ->withGetJobCall($this->createJob([]))
                    ->getMock(),
                'expectedResponse' => BadAddSourcesRequestResponse::createSourceMissingResponse('Test/test1.yml'),
            ],
        ];
    }

    private function createJobCreateRequest(string $label, string $callbackUrl): JobCreateRequest
    {
        $request = \Mockery::mock(JobCreateRequest::class);

        $request
            ->shouldReceive('getLabel')
            ->andReturn($label);

        $request
            ->shouldReceive('getCallbackUrl')
            ->andReturn($callbackUrl);

        return $request;
    }

    /**
     * @param array<mixed> $getSourcesReturnValue
     *
     * @return Job
     */
    private function createJob(array $getSourcesReturnValue): Job
    {
        $job = \Mockery::mock(Job::class);

        $job
            ->shouldReceive('getSources')
            ->andReturn($getSourcesReturnValue);

        return $job;
    }

    /**
     * @param Manifest|null $manifest
     * @param array<mixed> $sources
     *
     * @return AddSourcesRequest
     */
    private function createAddSourcesRequest(?Manifest $manifest, array $sources): AddSourcesRequest
    {
        $request = \Mockery::mock(AddSourcesRequest::class);

        $request
            ->shouldReceive('getManifest')
            ->andReturn($manifest);

        $request
            ->shouldReceive('getSources')
            ->andReturn($sources);

        return $request;
    }

    /**
     * @param string[] $testPaths
     *
     * @return Manifest
     */
    private function createManifest(array $testPaths): Manifest
    {
        $manifest = \Mockery::mock(Manifest::class);

        $manifest
            ->shouldReceive('getTestPaths')
            ->andReturn($testPaths);

        return $manifest;
    }
}
