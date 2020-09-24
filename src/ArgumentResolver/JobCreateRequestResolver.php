<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Exception\JobCreateRequestException;
use App\Request\JobCreateRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class JobCreateRequestResolver implements ArgumentValueResolverInterface
{
    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        return JobCreateRequest::class === $argument->getType();
    }

    /**
     * @param Request $request
     * @param ArgumentMetadata $argument
     *
     * @return \Generator<JobCreateRequest>
     *
     * @throws JobCreateRequestException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): \Generator
    {
        $postData = $request->request;

        $label = trim((string) $postData->get(JobCreateRequest::KEY_LABEL));
        if ('' === $label) {
            throw JobCreateRequestException::createLabelMissingException();
        }

        $callbackUrl = trim((string) $postData->get(JobCreateRequest::KEY_CALLBACK_URL));
        if ('' === $callbackUrl) {
            throw JobCreateRequestException::createCallbackUrlMissingException();
        }

        yield new JobCreateRequest($label, $callbackUrl);
    }
}
