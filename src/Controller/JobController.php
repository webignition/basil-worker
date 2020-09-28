<?php

namespace App\Controller;

use App\Entity\Job;
use App\Model\Manifest;
use App\Request\AddSourcesRequest;
use App\Request\JobCreateRequest;
use App\Response\BadAddSourcesRequestResponse;
use App\Response\BadJobCreateRequestResponse;
use App\Services\JobStore;
use App\Services\SourceStore;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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

        if ($this->jobStore->retrieve() instanceof Job) {
            return BadJobCreateRequestResponse::createJobAlreadyExistsResponse();
        }

        $job = Job::create($jobCreateRequest->getLabel(), $jobCreateRequest->getCallbackUrl());
        $this->jobStore->store($job);

        return new JsonResponse();
    }

    /**
     * @Route("/add-sources", name="add-sources", methods={"POST"})
     *
     * @param SourceStore $sourceStore
     * @param AddSourcesRequest $addSourcesRequest
     *
     * @return JsonResponse
     */
    public function addSources(SourceStore $sourceStore, AddSourcesRequest $addSourcesRequest): JsonResponse
    {
        $job = $this->jobStore->retrieve();

        if (!$job instanceof Job) {
            return BadAddSourcesRequestResponse::createJobMissingResponse();
        }

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

        return new JsonResponse();
    }
}