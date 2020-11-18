<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Callback\DelayedCallback;
use App\Event\Callback\CallbackHttpExceptionEvent;
use App\Event\Callback\CallbackHttpResponseEvent;
use App\Services\CallbackResponseHandler;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Model\TestCallback;
use App\Tests\Services\CallbackHttpExceptionEventSubscriber;
use App\Tests\Services\CallbackHttpResponseEventSubscriber;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Response;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class CallbackResponseHandlerTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;
    use TestClassServicePropertyInjectorTrait;

    private CallbackResponseHandler $callbackResponseHandler;
    private CallbackHttpExceptionEventSubscriber $exceptionEventSubscriber;
    private CallbackHttpResponseEventSubscriber $responseEventSubscriber;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    public function testHandleResponseEventDispatched()
    {
        $response = new Response(404);
        $callback = new TestCallback();
        self::assertSame(0, $callback->getRetryCount());

        $this->callbackResponseHandler->handleResponse($callback, $response);

        self::assertNull($this->exceptionEventSubscriber->getEvent());

        $event = $this->responseEventSubscriber->getEvent();
        self::assertInstanceOf(CallbackHttpResponseEvent::class, $event);

        $eventCallback = $event->getCallback();
        self::assertInstanceOf(DelayedCallback::class, $eventCallback);
        self::assertSame($eventCallback->getEntity(), $callback->getEntity());
        self::assertSame($response, $event->getResponse());
        self::assertSame(1, $callback->getRetryCount());
    }

    public function testHandleExceptionEventDispatched()
    {
        $exception = \Mockery::mock(ConnectException::class);
        $callback = new TestCallback();
        self::assertSame(0, $callback->getRetryCount());

        $this->callbackResponseHandler->handleClientException($callback, $exception);

        self::assertNull($this->responseEventSubscriber->getEvent());

        $event = $this->exceptionEventSubscriber->getEvent();
        self::assertInstanceOf(CallbackHttpExceptionEvent::class, $event);

        $eventCallback = $event->getCallback();
        self::assertInstanceOf(DelayedCallback::class, $eventCallback);
        self::assertSame($eventCallback->getEntity(), $callback->getEntity());
        self::assertSame($exception, $event->getException());
        self::assertSame(1, $callback->getRetryCount());
    }
}
