<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Request\AddSourcesRequest;
use App\Request\EncapsulatingRequestInterface;
use App\Request\JobCreateRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class EncapsulatingRequestResolver implements ArgumentValueResolverInterface
{
    private const SUPPORTED_CLASSES = [
        JobCreateRequest::class,
        AddSourcesRequest::class,
    ];

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        return in_array($argument->getType(), self::SUPPORTED_CLASSES);
    }

    /**
     * @return \Generator<EncapsulatingRequestInterface>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): \Generator
    {
        $type = $argument->getType();

        if (AddSourcesRequest::class === $type) {
            yield new AddSourcesRequest($request);
        }

        yield new JobCreateRequest($request);
    }
}
