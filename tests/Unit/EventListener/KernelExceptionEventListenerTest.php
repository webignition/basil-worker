<?php

declare(strict_types=1);

namespace App\Tests\Unit\ArgumentResolver;

use App\EventListener\KernelExceptionEventListener;
use App\Exception\JobCreateRequestException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class KernelExceptionEventListenerTest extends TestCase
{
    private KernelExceptionEventListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new KernelExceptionEventListener();
    }

    /**
     * @dataProvider onKernelExceptionDataProvider
     */
    public function testOnKernelException(ExceptionEvent $event, ExceptionEvent $expectedEvent)
    {
        $this->listener->onKernelException($event);

        self::assertEquals($expectedEvent, $event);
    }

    public function onKernelExceptionDataProvider(): array
    {
        $httpKernel = \Mockery::mock(HttpKernelInterface::class);
        $request = \Mockery::mock(Request::class);

        return [
            'event is unmodified' => [
                'event' => new ExceptionEvent(
                    $httpKernel,
                    $request,
                    HttpKernelInterface::MASTER_REQUEST,
                    new \Exception()
                ),
                'expectedEvent' => new ExceptionEvent(
                    $httpKernel,
                    $request,
                    HttpKernelInterface::MASTER_REQUEST,
                    new \Exception()
                ),
            ],
            'event response is set due to JobCreateRequestException' => [
                'event' => new ExceptionEvent(
                    $httpKernel,
                    $request,
                    HttpKernelInterface::MASTER_REQUEST,
                    JobCreateRequestException::createLabelMissingException()
                ),
                'expectedEvent' => $this->setResponseOnExceptionEvent(
                    new ExceptionEvent(
                        $httpKernel,
                        $request,
                        HttpKernelInterface::MASTER_REQUEST,
                        JobCreateRequestException::createLabelMissingException()
                    ),
                    new JsonResponse(
                        [
                            'type' => 'job-create-request',
                            'message' => 'label missing',
                            'code' => JobCreateRequestException::CODE_LABEL_MISSING,
                        ],
                        400
                    ),
                ),
            ],
        ];
    }

    private function setResponseOnExceptionEvent(ExceptionEvent $event, Response $response): ExceptionEvent
    {
        $event->setResponse($response);

        return $event;
    }
}
