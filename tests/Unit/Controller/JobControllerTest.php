<?php

namespace App\Tests\Unit\Controller;

use App\Controller\JobController;
use App\Exception\MissingTestSourceException;
use App\Repository\SourceRepository;
use App\Request\AddSourcesRequest;
use App\Request\JobCreateRequest;
use App\Response\BadAddSourcesRequestResponse;
use App\Response\BadJobCreateRequestResponse;
use App\Services\SourceFactory;
use App\Tests\Mock\Entity\MockJob;
use App\Tests\Mock\Entity\MockSource;
use App\Tests\Mock\Model\MockManifest;
use App\Tests\Mock\Model\MockUploadedSourceCollection;
use App\Tests\Mock\Repository\MockSourceRepository;
use App\Tests\Mock\Request\MockAddSourcesRequest;
use App\Tests\Mock\Request\MockJobCreateRequest;
use App\Tests\Mock\Services\MockJobFactory;
use App\Tests\Mock\Services\MockJobStore;
use App\Tests\Mock\Services\MockSourceFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use webignition\BasilWorker\PersistenceBundle\Services\JobFactory;
use webignition\BasilWorker\PersistenceBundle\Services\JobStore;

class JobControllerTest extends TestCase
{
    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(
        JobCreateRequest $jobCreateRequest,
        JobStore $jobStore,
        JobFactory $jobFactory,
        JsonResponse $expectedResponse
    ) {
        $controller = new JobController($jobStore);

        $response = $controller->create($jobFactory, $jobCreateRequest);

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
                'jobFactory' => (new MockJobFactory())->getMock(),
                'expectedResponse' => BadJobCreateRequestResponse::createLabelMissingResponse(),
            ],
            'callback url missing' => [
                'jobCreateRequest' => (new MockJobCreateRequest())
                    ->withGetLabelCall('label')
                    ->withGetCallbackUrlCall('')
                    ->getMock(),
                'jobStore' => (new MockJobStore())->getMock(),
                'jobFactory' => (new MockJobFactory())->getMock(),
                'expectedResponse' => BadJobCreateRequestResponse::createCallbackUrlMissingResponse(),
            ],
            'maximum duration missing' => [
                'jobCreateRequest' => (new MockJobCreateRequest())
                    ->withGetLabelCall('label')
                    ->withGetCallbackUrlCall('http://example.com')
                    ->withGetMaximumDurationInSecondsCall(null)
                    ->getMock(),
                'jobStore' => (new MockJobStore())->getMock(),
                'jobFactory' => (new MockJobFactory())->getMock(),
                'expectedResponse' => BadJobCreateRequestResponse::createMaximumDurationMissingResponse(),
            ],
            'job already exists' => [
                'jobCreateRequest' => (new MockJobCreateRequest())
                    ->withGetLabelCall('label')
                    ->withGetCallbackUrlCall('http://example.com')
                    ->withGetMaximumDurationInSecondsCall(10)
                    ->getMock(),
                'jobStore' => (new MockJobStore())
                    ->withHasCall(true)
                    ->getMock(),
                'jobFactory' => (new MockJobFactory())->getMock(),
                'expectedResponse' => BadJobCreateRequestResponse::createJobAlreadyExistsResponse(),
            ],
            'created' => [
                'jobCreateRequest' => (new MockJobCreateRequest())
                    ->withGetLabelCall('label')
                    ->withGetCallbackUrlCall('http://example.com')
                    ->withGetMaximumDurationInSecondsCall(10)
                    ->getMock(),
                'jobStore' => (new MockJobStore())
                    ->withHasCall(false)
                    ->getMock(),
                'jobFactory' => (new MockJobFactory())
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
        SourceRepository $sourceRepository,
        SourceFactory $sourceFactory,
        JsonResponse $expectedResponse
    ) {
        $controller = new JobController($jobStore);

        $response = $controller->addSources(
            $sourceRepository,
            $sourceFactory,
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
        $job = (new MockJob())
            ->getMock();

        $nonEmptyManifest = (new MockManifest())
            ->withGetTestPathsCall([
                'Test/test1.yml',
            ])
            ->getMock();

        $uploadedSources = (new MockUploadedSourceCollection())->getMock();
        $emptySourceFactory = (new MockSourceFactory())->getMock();

        $emptySourceRepository = (new MockSourceRepository())
            ->withFindAllCall([])
            ->getMock();

        return [
            'job missing' => [
                'addSourcesRequest' => \Mockery::mock(AddSourcesRequest::class),
                'jobStore' => (new MockJobStore())
                    ->withHasCall(false)
                    ->getMock(),
                'sourceRepository' => (new MockSourceRepository())->getMock(),
                'sourceFactory' => $emptySourceFactory,
                'expectedResponse' => BadAddSourcesRequestResponse::createJobMissingResponse(),
            ],
            'job has sources' => [
                'addSourcesRequest' => \Mockery::mock(AddSourcesRequest::class),
                'jobStore' => (new MockJobStore())
                    ->withHasCall(true)
                    ->withGetCall(
                        (new MockJob())
                            ->getMock()
                    )
                    ->getMock(),
                'sourceRepository' => (new MockSourceRepository())
                    ->withFindAllCall([
                        (new MockSource())
                            ->getMock(),
                    ])
                    ->getMock(),
                'sourceFactory' => $emptySourceFactory,
                'expectedResponse' => BadAddSourcesRequestResponse::createSourcesNotEmptyResponse(),
            ],
            'request manifest missing' => [
                'addSourcesRequest' => (new MockAddSourcesRequest())
                    ->withGetManifestCall(null)
                    ->getMock(),
                'jobStore' => (new MockJobStore())
                    ->withHasCall(true)
                    ->withGetCall($job)
                    ->getMock(),
                'sourceRepository' => $emptySourceRepository,
                'sourceFactory' => $emptySourceFactory,
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
                    ->withHasCall(true)
                    ->withGetCall($job)
                    ->getMock(),
                'sourceRepository' => $emptySourceRepository,
                'sourceFactory' => $emptySourceFactory,
                'expectedResponse' => BadAddSourcesRequestResponse::createManifestEmptyResponse(),
            ],
            'request source missing' => [
                'addSourcesRequest' => (new MockAddSourcesRequest())
                    ->withGetManifestCall($nonEmptyManifest)
                    ->withGetUploadedSourcesCall($uploadedSources)
                    ->getMock(),
                'jobStore' => (new MockJobStore())
                    ->withHasCall(true)
                    ->withGetCall($job)
                    ->getMock(),
                'sourceRepository' => $emptySourceRepository,
                'sourceFactory' => (new MockSourceFactory())
                    ->withCreateCollectionFromManifestCallThrowingException(
                        $nonEmptyManifest,
                        $uploadedSources,
                        new MissingTestSourceException('Test/test1.yml')
                    )
                    ->getMock(),
                'expectedResponse' => BadAddSourcesRequestResponse::createSourceMissingResponse('Test/test1.yml'),
            ],
        ];
    }
}
