<?php

namespace App\Controller;

use App\Entity\Job;
use App\Request\JobCreateRequest;
use App\Response\BadJobCreateRequestResponse;
use App\Services\JobStore;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    public function create(JobCreateRequest $jobCreateRequest)
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
}
