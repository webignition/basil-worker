<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

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
     */
    public function resolve(Request $request, ArgumentMetadata $argument): \Generator
    {
        yield new JobCreateRequest($request);
    }
}
