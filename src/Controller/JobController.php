<?php

declare(strict_types=1);

namespace App\Controller;

use App\Event\SourcesAddedEvent;
use App\Model\Manifest;
use App\Request\AddSourcesRequest;
use App\Request\JobCreateRequest;
use App\Response\BadAddSourcesRequestResponse;
use App\Response\BadJobCreateRequestResponse;
use App\Services\JobStore;
use App\Services\SourceStore;
use App\Services\TestStore;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class JobController extends AbstractController
{
    private JobStore $jobStore;

    public function __construct(JobStore $jobStore)
    {
        $this->jobStore = $jobStore;
    }

    /**
     * @Route("/create", name="create", methods={"POST"})
     *
     * @param JobCreateRequest $jobCreateRequest
     *
     * @return JsonResponse
     */
    public function create(JobCreateRequest $jobCreateRequest): JsonResponse
    {
        if ('' === $jobCreateRequest->getLabel()) {
            return BadJobCreateRequestResponse::createLabelMissingResponse();
        }

        if ('' === $jobCreateRequest->getCallbackUrl()) {
            return BadJobCreateRequestResponse::createCallbackUrlMissingResponse();
        }

        if (true === $this->jobStore->hasJob()) {
            return BadJobCreateRequestResponse::createJobAlreadyExistsResponse();
        }

        $this->jobStore->create($jobCreateRequest->getLabel(), $jobCreateRequest->getCallbackUrl());

        return new JsonResponse();
    }

    /**
     * @Route("/add-sources", name="add-sources", methods={"POST"})
     *
     * @param SourceStore $sourceStore
     * @param EventDispatcherInterface $eventDispatcher
     * @param AddSourcesRequest $addSourcesRequest
     *
     * @return JsonResponse
     */
    public function addSources(
        SourceStore $sourceStore,
        EventDispatcherInterface $eventDispatcher,
        AddSourcesRequest $addSourcesRequest
    ): JsonResponse {
        if (false === $this->jobStore->hasJob()) {
            return BadAddSourcesRequestResponse::createJobMissingResponse();
        }

        $job = $this->jobStore->getJob();

        if ([] !== $job->getSources()) {
            return BadAddSourcesRequestResponse::createSourcesNotEmptyResponse();
        }

        $manifest = $addSourcesRequest->getManifest();
        if (!$manifest instanceof Manifest) {
            return BadAddSourcesRequestResponse::createManifestMissingResponse();
        }

        $manifestTestPaths = $manifest->getTestPaths();
        if ([] === $manifestTestPaths) {
            return BadAddSourcesRequestResponse::createManifestEmptyResponse();
        }

        $requestSources = $addSourcesRequest->getSources();
        $jobSources = [];

        foreach ($manifestTestPaths as $manifestTestPath) {
            if (false === array_key_exists($manifestTestPath, $requestSources)) {
                return BadAddSourcesRequestResponse::createSourceMissingResponse($manifestTestPath);
            }

            $uploadedFile = $requestSources[$manifestTestPath];
            if (!$uploadedFile instanceof UploadedFile) {
                return BadAddSourcesRequestResponse::createSourceMissingResponse($manifestTestPath);
            }

            $sourceStore->store($uploadedFile, $manifestTestPath);
            $jobSources[] = $manifestTestPath;
        }

        $job->setSources($jobSources);
        $this->jobStore->store($job);

        $eventDispatcher->dispatch(new SourcesAddedEvent(), SourcesAddedEvent::NAME);

        return new JsonResponse();
    }

    /**
     * @Route("/status", name="status", methods={"GET"})
     *
     * @param TestStore $testStore
     *
     * @return JsonResponse
     */
    public function status(TestStore $testStore): JsonResponse
    {
        if (false === $this->jobStore->hasJob()) {
            return new JsonResponse([], 400);
        }

        $job = $this->jobStore->getJob();

        $tests = $testStore->findAll();

        $testData = [];
        foreach ($tests as $test) {
            $testData[] = $test->jsonSerialize();
        }

        $data = $job->jsonSerialize();
        $data['tests'] = $testData;

        return new JsonResponse($data);
    }
}
