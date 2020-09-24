<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Exception\RequestExceptionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

class KernelExceptionEventListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();

        if ($throwable instanceof RequestExceptionInterface) {
            $response = new JsonResponse(
                [
                    'type' => $throwable->getType(),
                    'message' => $throwable->getMessage(),
                    'code' => $throwable->getCode(),
                ],
                400
            );

            $event->setResponse($response);
        }
    }
}
