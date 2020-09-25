<?php

namespace App\Tests\Unit\Controller;

use App\Controller\JobController;
use App\Entity\Job;
use App\Request\JobCreateRequest;
use App\Response\BadJobCreateRequestResponse;
use App\Services\JobStore;
use PHPUnit\Framework\TestCase;
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
            'callback-url missing' => [
                'jobCreateRequest' => $this->createJobCreateRequest('label', ''),
                'jobStore' => \Mockery::mock(JobStore::class),
                'expectedResponse' => BadJobCreateRequestResponse::createCallbackUrlMissingResponse(),
            ],
            'job already exists' => [
                'jobCreateRequest' => $this->createJobCreateRequest('label', 'http://example.com'),
                'jobStore' => $this->createJobStore(\Mockery::mock(Job::class), null),
                'expectedResponse' => BadJobCreateRequestResponse::createJobAlreadyExistsResponse(),
            ],
            'created' => [
                'jobCreateRequest' => $this->createJobCreateRequest('label', 'http://example.com'),
                'jobStore' => $this->createJobStore(
                    null,
                    Job::create('label', 'http://example.com')
                ),
                'expectedResponse' => new JsonResponse(),
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

    private function createJobStore(?Job $retrieveReturnValue, ?Job $storeCallValue): JobStore
    {
        $store = \Mockery::mock(JobStore::class);

        $store
            ->shouldReceive('retrieve')
            ->andReturn($retrieveReturnValue);

        if ($storeCallValue instanceof Job) {
            $store
                ->shouldReceive('store')
                ->withArgs(function (Job $job) use ($storeCallValue) {
                    self::assertEquals($storeCallValue, $job);

                    return true;
                });
        }

        return $store;
    }
}
