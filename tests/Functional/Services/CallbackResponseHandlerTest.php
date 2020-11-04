<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Event\CallbackHttpExceptionEvent;
use App\Event\CallbackHttpResponseEvent;
use App\Services\CallbackResponseHandler;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\Model\Callback\MockCallback;
use App\Tests\Services\CallbackHttpExceptionEventSubscriber;
use App\Tests\Services\CallbackHttpResponseEventSubscriber;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Response;

class CallbackResponseHandlerTest extends AbstractBaseFunctionalTest
{
    private CallbackResponseHandler $callbackResponseHandler;
    private CallbackHttpExceptionEventSubscriber $exceptionEventSubscriber;
    private CallbackHttpResponseEventSubscriber $responseEventSubscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $callbackResponseHandler = self::$container->get(CallbackResponseHandler::class);
        if ($callbackResponseHandler instanceof CallbackResponseHandler) {
            $this->callbackResponseHandler = $callbackResponseHandler;
        }

        $exceptionEventSubscriber = self::$container->get(CallbackHttpExceptionEventSubscriber::class);
        if ($exceptionEventSubscriber instanceof CallbackHttpExceptionEventSubscriber) {
            $this->exceptionEventSubscriber = $exceptionEventSubscriber;
        }

        $responseEventSubscriber = self::$container->get(CallbackHttpResponseEventSubscriber::class);
        if ($responseEventSubscriber instanceof CallbackHttpResponseEventSubscriber) {
            $this->responseEventSubscriber = $responseEventSubscriber;
        }
    }

    public function testHandleResponseNoEventDispatched()
    {
        $response = new Response();
        $callback = MockCallback::createEmpty();

        $this->callbackResponseHandler->handleResponse($callback, $response);

        self::assertNull($this->exceptionEventSubscriber->getEvent());
        self::assertNull($this->responseEventSubscriber->getEvent());
    }

    public function testHandleResponseEventDispatched()
    {
        $response = new Response(404);
        $callback = MockCallback::createEmpty();

        $this->callbackResponseHandler->handleResponse($callback, $response);

        self::assertNull($this->exceptionEventSubscriber->getEvent());

        $event = $this->responseEventSubscriber->getEvent();
        self::assertInstanceOf(CallbackHttpResponseEvent::class, $event);

        self::assertSame($callback, $event->getCallback());
        self::assertSame($response, $event->getResponse());
    }

    public function testHandleExceptionEventDispatched()
    {
        $exception = \Mockery::mock(ConnectException::class);
        $callback = MockCallback::createEmpty();

        $this->callbackResponseHandler->handleClientException($callback, $exception);

        self::assertNull($this->responseEventSubscriber->getEvent());

        $event = $this->exceptionEventSubscriber->getEvent();
        self::assertInstanceOf(CallbackHttpExceptionEvent::class, $event);

        self::assertSame($callback, $event->getCallback());
        self::assertSame($exception, $event->getException());
    }
}
