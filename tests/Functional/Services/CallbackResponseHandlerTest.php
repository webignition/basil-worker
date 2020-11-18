<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Callback\DelayedCallback;
use App\Event\Callback\CallbackHttpErrorEvent;
use App\Services\CallbackResponseHandler;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Model\TestCallback;
use App\Tests\Services\CallbackHttpErrorEventSubscriber;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Response;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class CallbackResponseHandlerTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;
    use TestClassServicePropertyInjectorTrait;

    private CallbackResponseHandler $callbackResponseHandler;
    private CallbackHttpErrorEventSubscriber $httpErrorEventSubscriber;

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

        $event = $this->httpErrorEventSubscriber->getEvent();
        self::assertInstanceOf(CallbackHttpErrorEvent::class, $event);

        $eventCallback = $event->getCallback();
        self::assertInstanceOf(DelayedCallback::class, $eventCallback);
        self::assertSame($eventCallback->getEntity(), $callback->getEntity());
        self::assertSame($response, $event->getContext());
        self::assertSame(1, $callback->getRetryCount());
    }

    public function testHandleExceptionEventDispatched()
    {
        $exception = \Mockery::mock(ConnectException::class);
        $callback = new TestCallback();
        self::assertSame(0, $callback->getRetryCount());

        $this->callbackResponseHandler->handleClientException($callback, $exception);

        $event = $this->httpErrorEventSubscriber->getEvent();
        self::assertInstanceOf(CallbackHttpErrorEvent::class, $event);

        $eventCallback = $event->getCallback();
        self::assertInstanceOf(DelayedCallback::class, $eventCallback);
        self::assertSame($eventCallback->getEntity(), $callback->getEntity());
        self::assertSame($exception, $event->getContext());
        self::assertSame(1, $callback->getRetryCount());
    }
}
